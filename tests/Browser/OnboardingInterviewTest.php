<?php

namespace Tests\Browser;

use App\Models\Organization;
use App\Models\OrgContextVersion;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser coverage for the onboarding interview's JavaScript. The PHPUnit
 * feature suite (tests/Feature/OnboardingFlowTest.php etc.) exercises the
 * controller/service layer directly and never loads a page's <script>, so it
 * cannot catch a dead-JS regression (e.g. a view section named "script" vs
 * the layout yielding a section named "scripts"). These tests drive a real
 * browser against a real HTTP server so the fetch loop in
 * onboarding.blade.php actually runs.
 *
 * Requires AI_PROVIDER=fake (see .env.dusk.local and
 * AiProviderService::fakeGenerate()) because Dusk's server process cannot see
 * the container mocks PHPUnit tests bind with $this->instance(...).
 */
class OnboardingInterviewTest extends DuskTestCase
{
    /** Fixed follow-up text AiProviderService::fakeGenerate() returns for non-JSON calls. */
    private const FAKE_FOLLOWUP = 'And how large is the team you drive?';

    /**
     * migrate:fresh (not the DatabaseMigrations trait) deliberately: the
     * trait also runs migrate:rollback in tearDown, and this codebase's
     * 2024_03_25_055320_create_ai_content_detectors_table down() migration
     * has a pre-existing bug — it drops show_ai_detector/show_ai_plagiarism/
     * allow_ai_detector/allow_ai_plagiarism from the "pages" table, but up()
     * added them to "subscription_packages". Rolling back throws "no such
     * column" on a fresh sqlite file. migrate:fresh alone gives every test
     * full isolation without ever calling down(), sidestepping that bug
     * without touching a migration file (out of scope for this task).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /**
     * Dusk reuses the same browser (and therefore the same session cookie)
     * across every test in this class for speed — Laravel\Dusk\Http\
     * Controllers\UserController::login() calls Auth::login() but never
     * touches the session otherwise, so onboarding.* keys a previous test
     * wrote (turns, profile, pending_question) would otherwise leak into
     * the next test and make an "uncalibrated" user land straight on the
     * confirmation card. Clearing cookies first forces a brand-new session.
     */
    private function freshLoginAndVisit(Browser $browser, User $user, string $path): Browser
    {
        $browser->driver->manage()->deleteAllCookies();

        return $browser->loginAs($user)->visit($path);
    }

    private function uncalibratedCustomer(): User
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        return User::factory()->create([
            'email' => 'ceo@acme.com',
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => null,
        ]);
    }

    private function calibratedCustomer(): User
    {
        $org = Organization::create(['domain' => 'acme.com', 'name' => 'Acme']);

        return User::factory()->create([
            'email' => 'coo@acme.com',
            'user_type' => 'customer',
            'email_verified_at' => now(),
            'organization_id' => $org->id,
            'hierarchy_rank' => 30,
        ]);
    }

    /**
     * Type an answer, send it, and wait for the round trip to finish. The JS
     * sets sendBtn.disabled = true on click and clears it in a finally block
     * once the fetch settles, so waiting on the boolean DOM property (not the
     * WebDriver-reflected attribute, which is unreliable for booleans) is a
     * clean, sleep-free signal that the request completed either way.
     */
    private function submitAnswer(Browser $browser, string $answer): void
    {
        $browser->type('#oi-answer', $answer)
            ->click('#oi-send')
            ->waitUntil('!document.querySelector("#oi-send").disabled', 10);
    }

    public function test_an_uncalibrated_customer_is_routed_to_a_working_interview(): void
    {
        $user = $this->uncalibratedCustomer();

        $this->browse(function (Browser $browser) use ($user) {
            $this->freshLoginAndVisit($browser, $user, '/dashboard')
                ->waitForLocation('/dashboard/onboarding')
                ->assertPathIs('/dashboard/onboarding')
                ->waitForTextIn('#oi-question', 'anchor its intelligence in your daily reality')
                ->assertVisible('#oi-answer')
                ->assertVisible('#oi-send');
        });
    }

    public function test_the_interview_advances_through_to_the_confirmation_card(): void
    {
        $user = $this->uncalibratedCustomer();

        $this->browse(function (Browser $browser) use ($user) {
            $this->freshLoginAndVisit($browser, $user, '/dashboard/onboarding')
                ->waitForTextIn('#oi-question', 'anchor its intelligence in your daily reality');

            // Turn 1 -> 2: proves fetch + DOM update actually ran (the dead-JS
            // defect this suite exists to catch would leave the seed question
            // on screen forever).
            $this->submitAnswer($browser, 'I run learning and development for the org.');
            $browser->waitForTextIn('#oi-question', self::FAKE_FOLLOWUP)
                ->assertDontSeeIn('#oi-question', 'anchor its intelligence in your daily reality')
                ->assertSeeIn('#oi-thread', 'I run learning and development for the org.');

            // Turn 2 -> 3: the fake provider returns the same fixed follow-up
            // text, so the DOM-update proof here is the answer landing in the
            // thread and the input clearing, not a change in question text.
            $this->submitAnswer($browser, 'About 40 people across four regions.');
            $browser->assertSeeIn('#oi-thread', 'About 40 people across four regions.')
                ->assertValue('#oi-answer', '');

            // Turn 3: this is the summarize() call (jsonMode) -> confirmation card.
            $this->submitAnswer($browser, 'Weekly ops reviews, decisions made by consensus.');

            $browser->waitFor('#oi-card')
                ->assertVisible('#oi-card')
                ->assertMissing('#oi-ask')
                ->assertSeeIn('#oi-bullets', 'Chief Operating Officer')
                ->assertSee('Confirm & Begin Strategic Mapping');
        });
    }

    public function test_confirming_completes_calibration(): void
    {
        $user = $this->uncalibratedCustomer();

        $this->browse(function (Browser $browser) use ($user) {
            $this->freshLoginAndVisit($browser, $user, '/dashboard/onboarding')
                ->waitForTextIn('#oi-question', 'anchor its intelligence in your daily reality');

            $this->submitAnswer($browser, 'I run learning and development for the org.');
            $this->submitAnswer($browser, 'About 40 people across four regions.');
            $this->submitAnswer($browser, 'Weekly ops reviews, decisions made by consensus.');

            $browser->waitFor('#oi-card')
                ->press('Confirm & Begin Strategic Mapping')
                ->waitUntil("window.location.pathname != '/dashboard/onboarding'", 10)
                ->assertPathIsNot('/dashboard/onboarding');
        });

        $user = $user->fresh();

        $this->assertSame(50, (int) $user->hierarchy_rank);
        $this->assertTrue(
            OrgContextVersion::where('user_id', $user->id)->exists(),
            'confirming the interview should record an org_context_versions row for the user'
        );
    }

    public function test_a_calibrated_user_is_not_returned_to_the_interview(): void
    {
        $user = $this->calibratedCustomer();

        $this->browse(function (Browser $browser) use ($user) {
            $this->freshLoginAndVisit($browser, $user, '/dashboard/onboarding')
                ->waitForLocation('/dashboard')
                ->assertPathIsNot('/dashboard/onboarding');

            $browser->visit('/dashboard/onboarding?recalibrate=1')
                ->waitForTextIn('#oi-question', 'anchor its intelligence in your daily reality')
                ->assertPathIs('/dashboard/onboarding');
        });
    }

    public function test_a_failed_answer_request_surfaces_an_error_rather_than_undefined(): void
    {
        $user = $this->uncalibratedCustomer();

        $this->browse(function (Browser $browser) use ($user) {
            $this->freshLoginAndVisit($browser, $user, '/dashboard/onboarding')
                ->waitForTextIn('#oi-question', 'anchor its intelligence in your daily reality');

            // Cleaner trigger than a sentinel string in the fake provider: the
            // server validates `answer` as max:4000 before it ever reaches the
            // AI provider (OnboardingController::answer()). A textarea has no
            // client-side maxlength, so an over-length answer is a genuine,
            // reachable failure path — it makes the endpoint itself return a
            // non-OK (422) response, which is what the JS guard actually reacts
            // to. Injecting via script() because typing 4001 characters through
            // WebDriver key events is needlessly slow.
            $tooLong = str_repeat('x', 4001);
            $browser->script([
                "document.querySelector('#oi-answer').value = ".json_encode($tooLong),
            ]);

            $browser->click('#oi-send')
                ->waitFor('#oi-error')
                ->assertVisible('#oi-error')
                ->assertDontSeeIn('#oi-question', 'undefined')
                ->assertSeeIn('#oi-question', 'anchor its intelligence in your daily reality');
        });
    }
}
