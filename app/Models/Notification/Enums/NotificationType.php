<?php

namespace App\Models\Notification\Enums;

/**
 * The kind of a notification, which decides how it is rendered and which channels may carry it.
 *
 * Every case needs a `notifications.<value>` entry in the language files and a renderer that
 * supports it; NotificationTypeTest asserts both for every case.
 */
enum NotificationType: string
{
    /** A free-text message composed by an admin, shown verbatim in the language it was typed in. */
    case AdminCustom = 'admin_custom';

    public function titleLangKey(): string
    {
        return sprintf('notifications.%s.title', $this->value);
    }

    public function bodyLangKey(): string
    {
        return sprintf('notifications.%s.body', $this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::AdminCustom => 'fa-bullhorn',
        };
    }

    /**
     * Whether the message carries its own literal body text instead of a translatable language key.
     */
    public function isPlainText(): bool
    {
        return match ($this) {
            self::AdminCustom => true,
        };
    }

    /**
     * The channels this type may ever be delivered on.
     *
     * {@see self::AdminCustom} lists only {@see NotificationChannel::Internal}: an admin writing an
     * announcement must not be able to turn it into mail to the entire user base, so that is a
     * property of the type rather than something a preference or a form field can widen.
     *
     * @return array<int, NotificationChannel>
     */
    public function supportedChannels(): array
    {
        return match ($this) {
            self::AdminCustom => [NotificationChannel::Internal],
        };
    }
}
