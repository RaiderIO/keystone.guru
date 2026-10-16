<?php

namespace App\Email;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('mail.password_changed.subject'))
            ->line(__('mail.password_changed.changed'))
            ->line(__('mail.password_changed.not_you'))
            ->action(__('mail.password_changed.reset_password'), route('password.request'));
    }
}
