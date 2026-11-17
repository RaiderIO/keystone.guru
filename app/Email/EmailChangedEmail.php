<?php

namespace App\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent on demand to the address the account had before its e-mail address changed, so the previous owner of that
 * address hears about the change.
 */
class EmailChangedEmail extends Notification implements ShouldQueue
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
            ->subject(__('mail.email_changed.subject'))
            ->line(__('mail.email_changed.changed'))
            ->line(__('mail.email_changed.not_you'))
            ->action(__('mail.email_changed.contact_us'), route('misc.about'));
    }
}
