<?php

namespace App\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent on demand to the address the account had when its password changed: a queued notification to the User
 * would re-read the e-mail at delivery, by which time a hijacker may have changed it.
 */
class PasswordChangedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(AnonymousNotifiable $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('mail.password_changed.subject'))
            ->line(__('mail.password_changed.changed'))
            ->line(__('mail.password_changed.not_you'))
            ->action(__('mail.password_changed.reset_password'), route('password.request'));
    }
}
