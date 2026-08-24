<?php

namespace Tests\Feature;

use App\Mail\User\EmailConfirmationMail;
use App\Mail\User\WelcomeMail;
use App\Models\EmailTemplate;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class WelcomeMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * build() used to pass "array" to emails.verification, which is only `{!! $body !!}`,
     * so every welcome email rendered as 0 bytes.
     */
    public function test_welcome_mail_renders_the_template_body(): void
    {
        // The create_email_templates_table migration seeds its own welcome-email row,
        // so clear it or first() picks that one instead of the fixture below.
        EmailTemplate::where('type', 'welcome-email')->delete();

        EmailTemplate::create([
            'name' => 'Welcome Email',
            'subject' => 'Welcome Email',
            'slug' => 'welcome-email',
            'type' => 'welcome-email',
            'code' => '<p>Hello [name], welcome aboard.</p>',
            'is_active' => 1,
        ]);

        $user = User::factory()->create(['name' => 'Ada Lovelace']);

        $html = (new WelcomeMail($user))->render();

        $this->assertNotSame('', trim($html));
        $this->assertStringContainsString('Hello Ada Lovelace, welcome aboard.', $html);
    }

    /**
     * The seeding migration originally hardcoded writebot.themetags.com, which no longer
     * resolves, so every freshly-installed environment shipped emails with a broken logo
     * and a dead link. RefreshDatabase runs that migration, so this asserts the real seed.
     */
    public function test_seeded_email_templates_contain_no_dead_vendor_urls(): void
    {
        $templates = EmailTemplate::all();

        $this->assertNotEmpty($templates, 'migration should seed email templates');

        foreach ($templates as $template) {
            foreach (['themetags', 'login2design'] as $vendor) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $vendor,
                    (string) $template->code,
                    "Template [{$template->type}] still references {$vendor}"
                );
            }
        }
    }

    /** An inactive or absent template must fail the job, not deliver an empty message. */
    public function test_welcome_mail_refuses_to_send_without_an_active_template(): void
    {
        EmailTemplate::where('type', 'welcome-email')->delete();

        EmailTemplate::create([
            'name' => 'Welcome Email',
            'subject' => 'Welcome Email',
            'slug' => 'welcome-email',
            'type' => 'welcome-email',
            'code' => '<p>Hello [name].</p>',
            'is_active' => 0,
        ]);

        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);

        (new WelcomeMail($user))->render();
    }

    private function setSetting(string $entity, string $value): void
    {
        SystemSetting::updateOrCreate(['entity' => $entity], ['value' => $value]);

        // getSetting() caches the whole table for a day.
        Cache::forget('settings');
    }

    /**
     * Registration used to fire the verification email and the welcome email in
     * the same request, so every new user got two messages at once -- and the
     * welcome one said strictly less than the verification one it arrived with.
     */
    public function test_registration_with_verification_on_sends_only_the_verification_email(): void
    {
        Mail::fake();
        Notification::fake();

        $this->setSetting('registration_verification_with', 'email');
        $this->setSetting('welcome_email', '1');

        $this->post(route('register'), [
            'name' => 'Grace Hopper',
            'email' => 'grace@navy.mil',
            'phone' => '+15550600',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertNotNull(User::where('email', 'grace@navy.mil')->first(), 'registration did not create the user');

        Mail::assertSent(EmailConfirmationMail::class);
        Notification::assertNothingSent();
    }

    /** With verification off nothing else is sent, so the welcome email is still the one email. */
    public function test_registration_with_verification_off_still_sends_the_welcome_email(): void
    {
        Mail::fake();
        Notification::fake();

        $this->setSetting('registration_verification_with', 'disable');
        $this->setSetting('welcome_email', '1');

        $this->post(route('register'), [
            'name' => 'Alan Turing',
            'email' => 'alan@bletchley.uk',
            'phone' => '+15550601',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('email', 'alan@bletchley.uk')->first();
        $this->assertNotNull($user, 'registration did not create the user');

        Notification::assertSentTo($user, WelcomeNotification::class);
        Mail::assertNotSent(EmailConfirmationMail::class);
    }
}
