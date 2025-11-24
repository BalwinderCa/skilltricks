<?php

namespace App\Notifications;

use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\EmailManager;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        try {
            $notifiable->verification_code = rand(100000, 999999);
            $notifiable->save();

            $data['name'] = $notifiable->name;
            $data['email'] = $notifiable->email;
            $data['phone'] = $notifiable->phone;
            $data['active_url'] = route('email.verification.confirmation', encrypt($notifiable->verification_code));
            $template = EmailTemplate::where('type', 'registration-verification')->where('is_active', 1)->first();

            if(!$template) return false;

            $subject = $template->subject;
            $body    = EmailTemplate::emailTemplateBody($template->code, $data);

            return (new MailMessage)
                ->view('emails.verification', compact('body'))
                ->subject(localize($subject));

        } catch (\Throwable $th) {
            Log::info("Email verification notification email : " .$th->getMessage());
        }

    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
