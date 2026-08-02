<?php

namespace App\Mail\User;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
        ];

        $template = EmailTemplate::where('type', 'welcome-email')->where('is_active', 1)->first();

        // emails.verification is nothing but `{!! $body !!}`, so without a template there
        // is no message at all. Fail the job rather than deliver an empty email -- this
        // build() previously passed "array" to a view expecting "body" and sent 0 bytes.
        if (! $template) {
            throw new RuntimeException('No active "welcome-email" template; refusing to send an empty welcome email.');
        }

        commonLog("Welcome Mail send at for UserID: {$this->user->id}", ['user' => $this->user]);

        return $this
            ->view('emails.verification')
            ->with(['body' => EmailTemplate::emailTemplateBody($template->code, $data)])
            ->subject(localize($template->subject));
    }
}
