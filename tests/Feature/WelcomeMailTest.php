<?php

namespace Tests\Feature;

use App\Mail\User\WelcomeMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
