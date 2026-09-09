<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "You have been added to a team — here is how to sign in."
 *
 * Self-contained rather than driven by an EmailTemplate row like the welcome and
 * verification mails: those throw when their template is missing or inactive,
 * and an invitation that silently fails to send leaves someone with an account
 * they cannot reach. The trade is that this copy is not editable from the email
 * template admin.
 */
class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        /** Plain text, held only for the length of this send. */
        protected string $temporaryPassword,
    ) {}

    public function build()
    {
        return $this
            ->view('emails.team-invitation')
            ->with([
                'name' => $this->user->name,
                'email' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'organization' => optional($this->user->organization)->name
                    ?: optional($this->user->organization)->domain,
                'loginUrl' => route('login'),
                'systemTitle' => getSetting('system_title'),
            ])
            ->subject(localize('You have been added to').' '.getSetting('system_title'));
    }
}
