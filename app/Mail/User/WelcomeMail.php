<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        $array['view']    = 'emails.registrationSuccessful';
        $array['subject'] = localize('Registration Successful');
        $array['content'] = localize('Thanks for joining us. Your registration has been successfully completed.');


        commonLog("Welcome Mail send at for UserID: {$this->user->id}", ["user"=>$this->user]);

        return $this
                ->view('emails.verification')
                ->with(["array" =>$array])
                ->subject(localize('Email Verification - ') . env('APP_NAME'));

    }
}
