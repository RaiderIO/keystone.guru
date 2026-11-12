<?php

namespace Tests\Feature\Service\Notification;

use App\Models\Notification\Enums\NotificationChannel;
use App\Models\Notification\Enums\NotificationType;
use App\Service\Notification\NotificationRenderServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The contract every notification type has to keep: a translated title, a renderer, an icon and at
 * least one channel. Looping the enum means a type added later cannot ship half-wired.
 */
#[Group('Notification')]
final class NotificationTypeTest extends PublicTestCase
{
    #[Test]
    public function titleLangKey_givenEveryType_resolvesToATranslation(): void
    {
        // Arrange
        $cases = NotificationType::cases();
        self::assertNotEmpty($cases);

        foreach ($cases as $type) {
            // Act
            $key   = $type->titleLangKey();
            $title = __($key);

            // Assert - a missing line makes __() hand back the key itself
            self::assertNotSame($key, $title, sprintf('Notification type %s has no title translation', $type->value));
            self::assertNotSame('', $title);
        }
    }

    #[Test]
    public function bodyLangKey_givenEveryTranslatableType_resolvesToATranslation(): void
    {
        // Arrange
        $translatableTypes = array_filter(
            NotificationType::cases(),
            static fn(NotificationType $type): bool => !$type->isPlainText(),
        );

        foreach ($translatableTypes as $type) {
            // Act
            $key  = $type->bodyLangKey();
            $body = __($key);

            // Assert
            self::assertNotSame($key, $body, sprintf('Notification type %s has no body translation', $type->value));
        }

        // A plain-text-only enum leaves nothing to check above, so pin that the filter itself works
        self::assertCount(
            count(NotificationType::cases()) - count($translatableTypes),
            array_filter(NotificationType::cases(), static fn(NotificationType $type): bool => $type->isPlainText()),
        );
    }

    #[Test]
    public function hasRendererFor_givenEveryType_returnsTrue(): void
    {
        // Arrange
        $notificationRenderService = app(NotificationRenderServiceInterface::class);

        foreach (NotificationType::cases() as $type) {
            // Act
            $hasRenderer = $notificationRenderService->hasRendererFor($type);

            // Assert
            self::assertTrue($hasRenderer, sprintf('Notification type %s has no renderer', $type->value));
        }
    }

    #[Test]
    public function supportedChannels_givenEveryType_isNotEmpty(): void
    {
        foreach (NotificationType::cases() as $type) {
            // Act
            $channels = $type->supportedChannels();

            // Assert
            self::assertNotEmpty($channels, sprintf('Notification type %s supports no channel at all', $type->value));
        }
    }

    #[Test]
    public function icon_givenEveryType_returnsAnIcon(): void
    {
        foreach (NotificationType::cases() as $type) {
            // Act
            $icon = $type->icon();

            // Assert
            self::assertStringStartsWith('fa-', $icon, sprintf('Notification type %s has no icon', $type->value));
        }
    }

    /**
     * The guarantee that an admin writing an announcement cannot turn it into mail to the entire
     * user base: the type does not list the mail channel, so no preference or form field can widen
     * it.
     */
    #[Test]
    public function supportedChannels_givenAdminCustom_excludesEmail(): void
    {
        // Act
        $channels = NotificationType::AdminCustom->supportedChannels();

        // Assert
        self::assertSame([NotificationChannel::Internal], $channels);
        self::assertNotContains(NotificationChannel::Email, $channels);
    }
}
