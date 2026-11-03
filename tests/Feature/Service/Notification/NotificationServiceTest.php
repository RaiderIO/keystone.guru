<?php

namespace Tests\Feature\Service\Notification;

use App\Models\Laratrust\Role;
use App\Models\Notification\Enums\NotificationAudience;
use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\GlobalNotificationRead;
use App\Models\Notification\NotificationMessage;
use App\Models\Notification\UserNotification;
use App\Models\User;
use App\Service\Notification\NotificationServiceInterface;
use App\Service\Notification\NotificationTarget;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Notification')]
final class NotificationServiceTest extends PublicTestCase
{
    private const int ADMIN_USER_ID = 1;

    private NotificationServiceInterface $notificationService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = app(NotificationServiceInterface::class);
    }

    #[Test]
    public function notifyUser_givenUser_createsOneDeliveryAndCountsAsUnread(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createRecipient();

            // Act
            $notificationMessage = $this->notificationService->sendCustom(
                'A targeted message',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );

            // Assert
            self::assertSame(NotificationAudience::User, $notificationMessage->audience);
            self::assertSame(1, UserNotification::query()->where('notification_message_id', $notificationMessage->id)->count());
            self::assertSame(1, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * The whole point of the site-wide audience: no delivery rows, so the cost does not grow with
     * the number of accounts.
     */
    #[Test]
    public function sendCustom_givenEveryoneTarget_createsGlobalMessageWithoutDeliveries(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createRecipient();

            // Act
            $notificationMessage = $this->notificationService->sendCustom(
                'A site-wide message',
                NotificationTarget::everyone(),
                self::ADMIN_USER_ID,
            );

            // Assert
            self::assertSame(NotificationAudience::Global, $notificationMessage->audience);
            self::assertSame(0, UserNotification::query()->where('notification_message_id', $notificationMessage->id)->count());
            self::assertSame(1, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function getUnreadCount_givenGlobalMessageSentBeforeTheAccountExisted_returnsZero(): void
    {
        // Arrange
        $user = null;

        try {
            $this->notificationService->sendCustom(
                'Old news',
                NotificationTarget::everyone(),
                self::ADMIN_USER_ID,
            );
            // The account is younger than the announcement
            $user = $this->createRecipient(['created_at' => Carbon::now()->addMinute()]);

            // Act
            $unreadCount = $this->notificationService->getUnreadCount($user->fresh());

            // Assert
            self::assertSame(0, $unreadCount);
            self::assertCount(0, $this->notificationService->getFeed($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function markRead_givenGlobalMessage_writesOneReadRowAndClearsTheCount(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createRecipient();
            $notificationMessage = $this->notificationService->sendCustom(
                'A site-wide message',
                NotificationTarget::everyone(),
                self::ADMIN_USER_ID,
            );

            // Act
            $marked = $this->notificationService->markRead($user->fresh(), $notificationMessage->id);

            // Assert
            self::assertTrue($marked);
            self::assertSame(1, GlobalNotificationRead::query()
                ->where('user_id', $user->id)
                ->where('notification_message_id', $notificationMessage->id)
                ->count());
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * Somebody else's notification is not theirs to touch - and because a site-wide message has no
     * delivery row to check, the rejection has to come from the visibility rules.
     */
    #[Test]
    public function markRead_givenUserWhoWasNotNotified_returnsFalseAndLeavesItUnread(): void
    {
        // Arrange
        $recipient = null;
        $outsider  = null;

        try {
            $recipient           = $this->createRecipient();
            $outsider            = $this->createRecipient();
            $notificationMessage = $this->notificationService->sendCustom(
                'For the recipient only',
                NotificationTarget::user($recipient),
                self::ADMIN_USER_ID,
            );

            // Act
            $marked = $this->notificationService->markRead($outsider->fresh(), $notificationMessage->id);

            // Assert
            self::assertFalse($marked);
            self::assertSame(0, $this->notificationService->getUnreadCount($outsider->fresh()));
            // The recipient's own notification is untouched
            self::assertSame(1, $this->notificationService->getUnreadCount($recipient->fresh()));
        } finally {
            $this->cleanUp($recipient, $outsider);
        }
    }

    #[Test]
    public function markAllRead_givenTargetedAndGlobalUnread_clearsBoth(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createRecipient();
            $this->notificationService->sendCustom('Targeted', NotificationTarget::user($user), self::ADMIN_USER_ID);
            $this->notificationService->sendCustom('Site-wide', NotificationTarget::everyone(), self::ADMIN_USER_ID);
            self::assertSame(2, $this->notificationService->getUnreadCount($user->fresh()));

            // Act
            $this->notificationService->markAllRead($user->fresh());

            // Assert - and no read row per announcement was needed to get there
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
            self::assertSame(0, GlobalNotificationRead::query()->where('user_id', $user->id)->count());
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function getUnreadCount_givenUserWithNotificationsDisabled_returnsZero(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createRecipient(['notifications_enabled' => false]);
            $this->notificationService->sendCustom('Site-wide', NotificationTarget::everyone(), self::ADMIN_USER_ID);

            // Act
            $unreadCount = $this->notificationService->getUnreadCount($user->fresh());

            // Assert
            self::assertSame(0, $unreadCount);
            self::assertCount(0, $this->notificationService->getFeed($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function notifyTarget_givenUserWithNotificationsDisabled_createsNoDelivery(): void
    {
        // Arrange
        $disabledUser = null;
        $enabledUser  = null;

        try {
            $disabledUser = $this->createRecipient(['notifications_enabled' => false]);
            $enabledUser  = $this->createRecipient();

            // Act
            $notificationMessage = $this->notificationService->sendCustom(
                'To both',
                NotificationTarget::userIds([$disabledUser->id, $enabledUser->id]),
                self::ADMIN_USER_ID,
            );

            // Assert - only the user who wants notifications got one
            self::assertSame(1, UserNotification::query()->where('notification_message_id', $notificationMessage->id)->count());
            self::assertSame(1, UserNotification::query()
                ->where('notification_message_id', $notificationMessage->id)
                ->where('user_id', $enabledUser->id)
                ->count());
        } finally {
            $this->cleanUp($disabledUser, $enabledUser);
        }
    }

    #[Test]
    public function notifyTarget_givenTheSameDedupeKeyTwice_createsOneMessage(): void
    {
        // Arrange
        $user      = null;
        $dedupeKey = sprintf('test_dedupe:%s', uniqid('', true));

        try {
            $user = $this->createRecipient();

            // Act
            $first  = $this->notificationService->notifyUser($user, NotificationType::AdminCustom, [], null, $dedupeKey);
            $second = $this->notificationService->notifyUser($user, NotificationType::AdminCustom, [], null, $dedupeKey);

            // Assert
            self::assertSame($first->id, $second->id);
            self::assertSame(1, NotificationMessage::query()->where('dedupe_key', $dedupeKey)->count());
            self::assertSame(1, UserNotification::query()->where('notification_message_id', $first->id)->count());
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * A retried fan-out re-delivers chunks it already wrote, so delivering twice must not double up.
     */
    #[Test]
    public function deliverToUserIds_givenTheSameUserTwice_createsOneDelivery(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createRecipient();
            $notificationMessage = $this->notificationService->sendCustom(
                'Delivered twice',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );

            // Act
            $this->notificationService->deliverToUserIds($notificationMessage, [$user->id]);

            // Assert
            self::assertSame(1, UserNotification::query()->where('notification_message_id', $notificationMessage->id)->count());
            self::assertSame(1, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * An admin's announcement is shown exactly as typed - a body that happens to look like a
     * translation key stays that way instead of being resolved.
     */
    #[Test]
    public function getFeed_givenAdminCustomMessage_rendersTheBodyVerbatim(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createRecipient();
            $this->notificationService->sendCustom(
                'notifications.admin_custom.title',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );

            // Act
            $feed = $this->notificationService->getFeed($user->fresh());

            // Assert
            self::assertCount(1, $feed);
            self::assertSame('notifications.admin_custom.title', $feed->first()->body);
            // The title, unlike the body, is translated
            self::assertSame(__('notifications.admin_custom.title'), $feed->first()->title);
            self::assertFalse($feed->first()->read);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function getFeed_givenMoreMessagesThanTheLimit_returnsNewestFirstAndPagesWithTheCursor(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createRecipient();
            $this->notificationService->sendCustom('Oldest', NotificationTarget::user($user), self::ADMIN_USER_ID);
            $this->notificationService->sendCustom('Middle', NotificationTarget::user($user), self::ADMIN_USER_ID);
            $newest = $this->notificationService->sendCustom('Newest', NotificationTarget::user($user), self::ADMIN_USER_ID);

            // Act
            $firstPage  = $this->notificationService->getFeed($user->fresh(), 2);
            $secondPage = $this->notificationService->getFeed($user->fresh(), 2, $firstPage->last()->notificationMessageId);

            // Assert
            self::assertCount(2, $firstPage);
            self::assertSame($newest->id, $firstPage->first()->notificationMessageId);
            self::assertSame('Newest', $firstPage->first()->body);
            self::assertSame('Middle', $firstPage->last()->body);
            self::assertCount(1, $secondPage);
            self::assertSame('Oldest', $secondPage->first()->body);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function retract_givenDeliveredMessage_removesTheMessageAndItsDeliveries(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createRecipient();
            $notificationMessage = $this->notificationService->sendCustom(
                'To be retracted',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            $notificationMessageId = $notificationMessage->id;

            // Act
            $this->notificationService->retract($notificationMessage);

            // Assert
            self::assertNull(NotificationMessage::query()->find($notificationMessageId));
            self::assertSame(0, UserNotification::query()->where('notification_message_id', $notificationMessageId)->count());
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * A fan-out job still running when its message is retracted writes deliveries pointing at a
     * missing message. Those must not strand a badge the reader can never clear.
     */
    #[Test]
    public function getUnreadCount_givenADeliveryWhoseMessageIsGone_doesNotCountIt(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createRecipient();
            $notificationMessage = $this->notificationService->sendCustom(
                'Retracted mid-fan-out',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            $notificationMessageId = $notificationMessage->id;
            self::assertSame(1, $this->notificationService->getUnreadCount($user->fresh()));

            // Act - the message goes, the delivery row is re-created by the job that was still running
            NotificationMessage::query()->whereKey($notificationMessageId)->delete();
            UserNotification::query()->insertOrIgnore([
                'user_id'                 => $user->id,
                'notification_message_id' => $notificationMessageId,
                'read_at'                 => null,
                'created_at'              => Carbon::now(),
            ]);

            // Assert
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
            self::assertCount(0, $this->notificationService->getFeed($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function getUnreadCount_givenAnExpiredTargetedNotification_doesNotCountIt(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createRecipient();
            $notificationMessage = $this->notificationService->sendCustom(
                'Expires in a moment',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            self::assertSame(1, $this->notificationService->getUnreadCount($user->fresh()));

            // Act
            NotificationMessage::query()->whereKey($notificationMessage->id)
                ->update(['expires_at' => Carbon::now()->subMinute()]);

            // Assert - the count and the feed agree
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
            self::assertCount(0, $this->notificationService->getFeed($user->fresh()));
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRecipient(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        return $user;
    }

    private function cleanUp(?User ...$users): void
    {
        NotificationMessage::query()->where('created_by', self::ADMIN_USER_ID)->get()
            ->each(function (NotificationMessage $notificationMessage): void {
                UserNotification::query()->where('notification_message_id', $notificationMessage->id)->delete();
                GlobalNotificationRead::query()->where('notification_message_id', $notificationMessage->id)->delete();
                $notificationMessage->delete();
            });

        NotificationMessage::query()->where('dedupe_key', 'like', 'test_dedupe:%')->get()
            ->each(function (NotificationMessage $notificationMessage): void {
                UserNotification::query()->where('notification_message_id', $notificationMessage->id)->delete();
                $notificationMessage->delete();
            });

        foreach ($users as $user) {
            if ($user !== null) {
                UserNotification::query()->where('user_id', $user->id)->delete();
                GlobalNotificationRead::query()->where('user_id', $user->id)->delete();
                $user->delete();
            }
        }
    }
}
