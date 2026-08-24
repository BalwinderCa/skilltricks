<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\AI\OnboardingAgentService;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    private const TURNS_KEY = 'onboarding.turns';

    private const PROFILE_KEY = 'onboarding.profile';

    private const FAILURES_KEY = 'onboarding.failures';

    private const PENDING_KEY = 'onboarding.pending_question';

    /** Summarize attempts before falling back, bounding billed calls per interview. */
    private const MAX_SUMMARY_ATTEMPTS = 2;

    public function __construct(
        protected OnboardingAgentService $agent,
        protected OrganizationService $organizations,
    ) {
        $this->middleware('auth');
    }

    /**
     * An already-calibrated user is out unless they asked to recalibrate.
     *
     * Re-running the interview is a re-roll: the model picks the rank, and the
     * cascade's ">=" tie rule makes the last high claim stick, so an unbounded
     * retry loop is both an escalation path and unbounded spend (throttle:20,1
     * bounds a minute, not a total). The ?recalibrate=1 opt-in keeps the one
     * legitimate case — an owner correction that left the organization with no
     * active context — recoverable, as a deliberate act.
     */
    private function blockedFromInterview(Request $request): bool
    {
        return $request->user()?->hierarchy_rank !== null
            && ! $request->boolean('recalibrate');
    }

    /**
     * The same rule for the POST endpoints, which carry no ?recalibrate flag.
     * A recalibration is opened through index(), which seeds the session — so
     * an in-flight interview is the proof that opt-in happened. Without this,
     * a calibrated user can drive answer() directly and re-roll their rank.
     */
    private function blockedFromPost(Request $request): bool
    {
        if ($request->user()?->hierarchy_rank === null) {
            return false;
        }

        return ! $request->session()->hasAny([self::PENDING_KEY, self::TURNS_KEY, self::PROFILE_KEY]);
    }

    /** Turn 1: fixed copy, no LLM call. */
    public function index(Request $request)
    {
        if ($this->blockedFromInterview($request)) {
            return redirect()->route('writebot.dashboard');
        }

        $turns = $request->session()->get(self::TURNS_KEY, []);
        $profile = $request->session()->get(self::PROFILE_KEY);
        $question = $request->session()->get(self::PENDING_KEY, OnboardingAgentService::SEED_QUESTION);

        // answer() forgets the pending question once it has a profile, so
        // re-seeding it here would resurrect the seed question underneath the
        // confirmation card. The view hides the question box when a profile is
        // set; leave the session alone in that state.
        if (! $profile) {
            $request->session()->put(self::PENDING_KEY, $question);
        }

        return view('backend.pages.onboarding', [
            'turns' => $turns,
            'question' => $question,
            'profile' => $profile,
        ]);
    }

    /**
     * Record an answer and either ask the next question or produce the
     * confirmation card.
     */
    public function answer(Request $request)
    {
        if ($this->blockedFromPost($request)) {
            return redirect()->route('writebot.dashboard');
        }

        $validated = $request->validate([
            'answer' => 'required|string|max:4000',
        ]);

        // Already summarized: return the stored card instead of paying for
        // another model call. Without this, replaying the endpoint bills a
        // summarize() on every POST, forever.
        if ($done = $request->session()->get(self::PROFILE_KEY)) {
            return response()->json(['done' => true, 'profile' => $done]);
        }

        $turns = $request->session()->get(self::TURNS_KEY, []);

        // The server owns the question. The client used to echo it back and we
        // stored that echo verbatim — an unvalidated request field flowing into
        // the prompt that decides the user's rank. The rank is read from the
        // session precisely so the request body cannot influence it, and
        // trusting this echo reopened that door indirectly.
        $question = $request->session()->get(self::PENDING_KEY, OnboardingAgentService::SEED_QUESTION);

        $turns[] = ['question' => $question, 'answer' => $validated['answer']];
        $request->session()->put(self::TURNS_KEY, $turns);

        $existing = optional(optional($request->user())->organization)->activeContext;

        if (count($turns) < OnboardingAgentService::QUESTION_TURNS) {
            $next = $this->agent->nextQuestion($turns, $existing);
            $request->session()->put(self::PENDING_KEY, $next);

            return response()->json(['done' => false, 'question' => $next]);
        }

        $profile = $this->agent->summarize($turns, $existing);

        if ($profile === null) {
            $failures = (int) $request->session()->increment(self::FAILURES_KEY);

            if ($failures < self::MAX_SUMMARY_ATTEMPTS) {
                $retry = 'Sorry, could you say that once more?';
                $request->session()->put(self::PENDING_KEY, $retry);

                return response()->json(['done' => false, 'question' => $retry]);
            }

            // Provider is failing. Stop paying for retries and complete the user
            // deterministically at the individual-contributor floor: they reach
            // the platform, and rank 10 grants no authority over anyone else's
            // context. The org owner can correct it, and the transcript is kept.
            $profile = $this->fallbackProfile($turns);
        }

        $request->session()->put(self::PROFILE_KEY, $profile);
        $request->session()->forget([self::FAILURES_KEY, self::PENDING_KEY]);

        return response()->json(['done' => true, 'profile' => $profile]);
    }

    /**
     * Minimal profile used when the model cannot produce a usable summary.
     * Rank is the floor by construction — never inferred from what the user
     * claimed, because nothing validated it.
     */
    private function fallbackProfile(array $turns): array
    {
        $firstAnswer = trim((string) ($turns[0]['answer'] ?? ''));

        return [
            'role' => $firstAnswer !== '' ? mb_substr($firstAnswer, 0, 200) : 'Not specified',
            'rank' => 10,
            'scale' => '',
            'governance' => '',
            'frictions' => [],
            'summary_bullets' => [
                'Recorded without AI calibration — the assistant was unavailable.',
                'Your organization owner can adjust your seniority from the profile page.',
            ],
        ];
    }

    /**
     * Commit the calibration.
     *
     * The rank comes from the session profile the agent produced, never from the
     * request body — otherwise any user could POST rank 60 and take over their
     * organization's active baseline without an interview.
     */
    public function confirm(Request $request)
    {
        if ($this->blockedFromPost($request)) {
            return redirect()->route('writebot.dashboard');
        }

        $profile = $request->session()->get(self::PROFILE_KEY);
        $user = $request->user();

        if (! is_array($profile) || empty($profile['role'])) {
            flash(localize('Please finish the calibration before confirming.'))->error();

            return redirect()->route('onboarding.index');
        }

        $org = $user->organization
            ?: $this->organizations->resolveForUser($user);

        if (empty($user->organization_id)) {
            $this->organizations->attachUser($user, $org);
        }

        try {
            $this->organizations->recordContext(
                $org,
                $user,
                (int) $profile['rank'],
                $profile,
                $request->session()->get(self::TURNS_KEY, [])
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('Onboarding produced an invalid rank', [
                'user_id' => $user->id,
                'rank' => $profile['rank'] ?? null,
            ]);

            $request->session()->forget(self::PROFILE_KEY);

            flash(localize('Something went wrong saving your profile. Please try again.'))->error();

            return redirect()->route('onboarding.index');
        }

        $request->session()->forget([self::TURNS_KEY, self::PROFILE_KEY, self::FAILURES_KEY]);

        flash(localize('Calibration complete.'))->success();

        return redirect()->route('newusers-new-chat.index');
    }
}
