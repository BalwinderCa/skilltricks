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

    public function __construct(
        protected OnboardingAgentService $agent,
        protected OrganizationService $organizations,
    ) {
        $this->middleware('auth');
    }

    /** Turn 1: fixed copy, no LLM call. */
    public function index(Request $request)
    {
        $turns = $request->session()->get(self::TURNS_KEY, []);

        return view('backend.pages.onboarding', [
            'turns' => $turns,
            'question' => $turns === [] ? OnboardingAgentService::SEED_QUESTION : end($turns)['question'],
            'profile' => $request->session()->get(self::PROFILE_KEY),
        ]);
    }

    /**
     * Record an answer and either ask the next question or produce the
     * confirmation card.
     */
    public function answer(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'answer' => 'required|string|max:4000',
        ]);

        $turns = $request->session()->get(self::TURNS_KEY, []);
        $turns[] = ['question' => $validated['question'], 'answer' => $validated['answer']];
        $request->session()->put(self::TURNS_KEY, $turns);

        $existing = optional(optional($request->user())->organization)->activeContext;

        if (count($turns) < OnboardingAgentService::QUESTION_TURNS) {
            return response()->json([
                'done' => false,
                'question' => $this->agent->nextQuestion($turns, $existing),
            ]);
        }

        $profile = $this->agent->summarize($turns, $existing);

        if ($profile === null) {
            $failures = $request->session()->increment(self::FAILURES_KEY);

            // Two bad summaries in a row: fall back to a minimal form rather than
            // locking the user out of the platform on a provider hiccup.
            return response()->json([
                'done' => false,
                'fallback' => $failures >= 2,
                'question' => $failures >= 2
                    ? 'One more time, in your own words: what is your role, and how senior is it?'
                    : 'Sorry, could you say that once more?',
            ]);
        }

        $request->session()->put(self::PROFILE_KEY, $profile);
        $request->session()->forget(self::FAILURES_KEY);

        return response()->json(['done' => true, 'profile' => $profile]);
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

            flash(localize('Something went wrong saving your profile. Please try again.'))->error();

            return redirect()->route('onboarding.index');
        }

        $request->session()->forget([self::TURNS_KEY, self::PROFILE_KEY, self::FAILURES_KEY]);

        flash(localize('Calibration complete.'))->success();

        return redirect()->route('newusers-new-chat.index');
    }
}
