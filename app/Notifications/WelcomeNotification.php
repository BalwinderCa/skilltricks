<?php

namespace App\Notifications;

use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\EmailManager;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification
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
        $data['name']  = $notifiable->name;
        $data['email'] = $notifiable->email;
        $data['phone'] = $notifiable->phone;
        $template = EmailTemplate::where('type', 'welcome-email')->where('is_active', 1)->first();
        if(!$template) return false;
        $subject = $template->subject;
        $body    = EmailTemplate::emailTemplateBody($template->code, $data);
        return (new MailMessage)
            ->view('emails.verification', compact('body'))
            ->subject(localize( $subject));
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
