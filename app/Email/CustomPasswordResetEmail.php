<?php

namespace App\Email;

/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 8-10-2018
 * Time: 14:03
 */

class CustomPasswordResetEmail extends \Illuminate\Auth\Notifications\ResetPassword
{
    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage())
            ->line('We are sending this email because we recieved a forgot password request.')
            ->action('Reset Password', url(config('app.url') . route('password.reset', $this->token, false)))
            ->line('If you did not request a password reset, no further action is required. Please contact us if you did not submit this request.');
    }
}