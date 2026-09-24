<?php

namespace App\Services;

use App\Mail\EmailManager;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Strategy alerts go out in-app (the navbar bell) and by email. The email is
 * best-effort: a failure is reported and never blocks the action behind it.
 */
class StrategyAlerts
{
    /** $url is relative: the notification controller redirects to '/'.$url. */
    public function send(User $to, string $title, string $url, string $body, string $type): void
    {
        saveNotification($title, $url, 'customer', (int) $to->id, null, $type, $body);

        if (! $to->email) {
            return;
        }
        try {
            Mail::to($to->email)->queue(new EmailManager([
                'view' => 'emails.strategy-alert',
                'from' => config('custom.mail_from_address'),
                'subject' => $title,
                'title' => $title,
                'body' => $body,
                'link' => url($url),
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
