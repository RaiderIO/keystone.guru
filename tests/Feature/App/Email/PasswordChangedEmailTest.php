<?php

namespace Tests\Feature\App\Email;

use App\Email\PasswordChangedEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Email')]
final class PasswordChangedEmailTest extends PublicTestCase
{
    #[Test]
    public function toMail_givenUser_returnsMailLinkingToThePasswordReset(): void
    {
        // Arrange
        $notification = new PasswordChangedEmail();
        $notifiable   = new AnonymousNotifiable()->route('mail', 'owner@example.com');

        // Act
        $mailMessage = $notification->toMail($notifiable);

        // Assert
        $this->assertSame(['mail'], $notification->via($notifiable));
        $this->assertSame(__('mail.password_changed.subject'), $mailMessage->subject);
        $this->assertSame([
            __('mail.password_changed.changed'),
            __('mail.password_changed.not_you'),
        ], $mailMessage->introLines);
        $this->assertSame(__('mail.password_changed.reset_password'), $mailMessage->actionText);
        $this->assertSame(route('password.request'), $mailMessage->actionUrl);
    }
}
