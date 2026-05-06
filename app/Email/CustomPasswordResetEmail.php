<?php

namespace App\Email;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 8-10-2018
 * Time: 14:03
 */
class CustomPasswordResetEmail extends ResetPassword
{
    #[\Override]
    public function toMail($notifiable)
    {
        return new MailMessage()
            ->subject('Password Reset Notification')
            ->line('We are sending this email because we received a forgot password request.')
            ->action('Reset Password', url(config('app.url') . route('password.reset', ['token' => $this->token], false)))
            ->line('If you did not request a password reset, no further action is required. Please contact us if you did not submit this request.');
    }
}
