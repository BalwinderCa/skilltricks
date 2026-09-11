<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which dialogs can be dismissed.
 *
 * inc/ui-dialog.blade.php gives every <dialog class="st-modal"> a close button
 * and backdrop-click, but only when it already has a Cancel button — that is the
 * marker for "this has somewhere to cancel to". The set-password dialog
 * deliberately has none, and also blocks Escape, because there is nothing behind
 * it until a password is chosen.
 *
 * So the marker is load-bearing: adding a Cancel button to that view would
 * silently make a mandatory dialog dismissible. This pins it.
 */
class DialogDismissalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    public function test_the_forced_password_dialog_stays_undismissable(): void
    {
        $user = User::factory()->create([
            'user_type' => 'customer', 'email_verified_at' => now(),
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertOk();

        // Scoped to the dialog element: "st-dialog-cancel" also appears in
        // ui-dialog.blade.php's stylesheet on every page, so asserting against
        // the whole response would pass no matter what this dialog contains.
        preg_match('#<dialog[^>]*id="setPasswordDialog".*?</dialog>#s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches, 'the set-password dialog should render');
        // The marker ui-dialog.blade.php keys on. Its absence is what keeps the
        // close button and the backdrop click off this dialog.
        $this->assertStringNotContainsString('st-dialog-cancel', $matches[0]);
    }
}
