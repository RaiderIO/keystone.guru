<?php

namespace Tests\Feature\Controller;

use App\Features\Notifications;
use App\Models\Laratrust\Role;
use App\Models\Notification\GlobalNotificationRead;
use App\Models\Notification\NotificationMessage;
use App\Models\Notification\UserNotification;
use App\Models\User;
use App\Service\Notification\NotificationServiceInterface;
use App\Service\Notification\NotificationTarget;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Notification')]
final class NotificationControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID = 1;

    private NotificationServiceInterface $notificationService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = app(NotificationServiceInterface::class);
    }

    /**
     * The feature gate is what keeps this merged dark, so a user it does not resolve for must not
     * reach the page at all.
     */
    #[Test]
    public function index_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser();
            Feature::for($user)->deactivate(Notifications::class);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.index'));

            // Assert
            $response->assertNotFound();
        } finally {
            if ($user !== null) {
                Feature::for($user)->forget(Notifications::class);
            }
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function index_givenUserWithANotification_showsIt(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser(featureActive: true);
            $this->notificationService->sendCustom(
                'Please join the beta test',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.index'));

            // Assert
            $response->assertOk();
            $response->assertSee('Please join the beta test');
            $response->assertSee(__('notifications.admin_custom.title'));
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function read_givenRecipient_marksItRead(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Mark me',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            $this->be($user);

            // Act
            $response = $this->post(route('notifications.read', ['notificationMessage' => $notificationMessage->id]));

            // Assert
            $response->assertRedirect(route('notifications.index'));
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function read_givenUserWhoWasNotNotified_returnsNotFound(): void
    {
        // Arrange
        $recipient = null;
        $outsider  = null;

        try {
            $recipient           = $this->createUser();
            $outsider            = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Not for the outsider',
                NotificationTarget::user($recipient),
                self::ADMIN_USER_ID,
            );
            $this->be($outsider);

            // Act
            $response = $this->post(route('notifications.read', ['notificationMessage' => $notificationMessage->id]));

            // Assert
            $response->assertNotFound();
            self::assertSame(1, $this->notificationService->getUnreadCount($recipient->fresh()));
        } finally {
            $this->forgetFeature($outsider);
            $this->cleanUp($recipient, $outsider);
        }
    }

    #[Test]
    public function readAll_givenUserWithUnreadNotifications_clearsThemAll(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser(featureActive: true);
            $this->notificationService->sendCustom('Targeted', NotificationTarget::user($user), self::ADMIN_USER_ID);
            $this->notificationService->sendCustom('Site-wide', NotificationTarget::everyone(), self::ADMIN_USER_ID);
            $this->be($user);

            // Act
            $response = $this->post(route('notifications.readall'));

            // Assert
            $response->assertRedirect(route('notifications.index'));
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function go_givenNotificationWithALink_marksItReadAndRedirectsThere(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Go somewhere',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
                '/discover',
            );
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.go', ['notificationMessage' => $notificationMessage->id]));

            // Assert
            $response->assertRedirect('/discover');
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    /**
     * A url is validated on the way in, but a tampered row must not be able to bounce a reader off
     * the site either.
     */
    #[Test]
    public function go_givenNotificationWithAnAbsoluteUrl_redirectsToTheFeedInstead(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Off-site',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            NotificationMessage::query()->whereKey($notificationMessage->id)
                ->update(['url' => 'https://evil.example.com']);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.go', ['notificationMessage' => $notificationMessage->id]));

            // Assert
            $response->assertRedirect(route('notifications.index'));
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    /**
     * A browser reads `//evil.example.com` as a protocol-relative URL, and Laravel's redirect
     * helper passes it through untouched - so a leading slash alone is not enough to keep a reader
     * on this site.
     */
    #[Test]
    public function go_givenNotificationWithAProtocolRelativeUrl_redirectsToTheFeedInstead(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Protocol relative',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            NotificationMessage::query()->whereKey($notificationMessage->id)
                ->update(['url' => '//evil.example.com']);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.go', ['notificationMessage' => $notificationMessage->id]));

            // Assert
            $response->assertRedirect(route('notifications.index'));
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function index_givenAnExpiredNotification_doesNotShowItOrCountIt(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Already expired',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            NotificationMessage::query()->whereKey($notificationMessage->id)
                ->update(['expires_at' => Carbon::now()->subMinute()]);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.index'));

            // Assert
            $response->assertOk();
            $response->assertDontSee('Already expired');
            self::assertSame(0, $this->notificationService->getUnreadCount($user->fresh()));
            // And it cannot be marked read either, since there is nothing of it to see
            $this->post(route('notifications.read', ['notificationMessage' => $notificationMessage->id]))
                ->assertNotFound();
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function popover_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser();
            Feature::for($user)->deactivate(Notifications::class);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.popover'));

            // Assert
            $response->assertNotFound();
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function popover_givenGuest_redirectsToLogin(): void
    {
        // Act
        $response = $this->get(route('notifications.popover'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * The pop-out is a short list: the newest few, each linking through to its target, plus the unread
     * count the bell's badge takes over.
     */
    #[Test]
    public function popover_givenMoreNotificationsThanFit_showsTheNewestAndTheUnreadCount(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser(featureActive: true);
            for ($i = 1; $i <= 6; $i++) {
                $this->notificationService->sendCustom(
                    sprintf('Popover notification %d', $i),
                    NotificationTarget::user($user),
                    self::ADMIN_USER_ID,
                );
            }
            $linked = $this->notificationService->sendCustom(
                'Popover notification with a link',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
                '/discover',
            );
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.popover'));

            // Assert
            $response->assertOk();
            $response->assertSee('data-notification-bell-unread-count="7"', false);
            $response->assertSee('Popover notification with a link');
            $response->assertSee(route('notifications.go', ['notificationMessage' => $linked->id]));
            $response->assertSee('Popover notification 6');
            $response->assertSee('Popover notification 3');
            $response->assertDontSee('Popover notification 2');
            $response->assertDontSee('Popover notification 1');
            $response->assertSee('notification_bell_item_unread', false);
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function popover_givenUserWithoutNotifications_showsTheEmptyState(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser(featureActive: true);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.popover'));

            // Assert
            $response->assertOk();
            $response->assertSee('data-notification-bell-unread-count="0"', false);
            $response->assertSee(__('view_notification.popover.empty'));
            $response->assertDontSee('notification_bell_item', false);
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function popover_givenReadNotification_doesNotMarkItUnread(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(featureActive: true);
            $notificationMessage = $this->notificationService->sendCustom(
                'Already seen',
                NotificationTarget::user($user),
                self::ADMIN_USER_ID,
            );
            $this->notificationService->markRead($user, $notificationMessage->id);
            $this->be($user);

            // Act
            $response = $this->get(route('notifications.popover'));

            // Assert
            $response->assertOk();
            $response->assertSee('Already seen');
            $response->assertSee('data-notification-bell-unread-count="0"', false);
            $response->assertDontSee('notification_bell_item_unread', false);
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function header_givenUserWithUnreadNotifications_showsTheBellWithItsCount(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser(featureActive: true);
            $this->notificationService->sendCustom('One', NotificationTarget::user($user), self::ADMIN_USER_ID);
            $this->notificationService->sendCustom('Two', NotificationTarget::user($user), self::ADMIN_USER_ID);
            $this->be($user);

            // Act
            $response = $this->get(route('home'));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf('data-notification-bell-url="%s"', route('notifications.popover')), false);
            $response->assertSee('<span data-notification-bell-count>2</span>', false);
            $response->assertDontSee('text-bg-warning d-none', false);
            $response->assertSee(sprintf('href="%s"', route('notifications.index')), false);
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function header_givenUserWithoutUnreadNotifications_hidesTheBadge(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser(featureActive: true);
            $this->be($user);

            // Act
            $response = $this->get(route('home'));

            // Assert
            $response->assertOk();
            $response->assertSee('data-notification-bell-url', false);
            $response->assertSee('text-bg-warning d-none', false);
            $response->assertSee('<span data-notification-bell-count>0</span>', false);
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function header_givenFeatureInactive_hasNoBell(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUser();
            Feature::for($user)->deactivate(Notifications::class);
            $this->be($user);

            // Act
            $response = $this->get(route('home'));

            // Assert
            $response->assertOk();
            $response->assertSee('id="navbarDropdown"', false);
            $response->assertDontSee('data-notification-bell-url', false);
        } finally {
            $this->forgetFeature($user);
            $this->cleanUp($user);
        }
    }

    private function createUser(bool $featureActive = false): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        if ($featureActive) {
            Feature::for($user)->activate(Notifications::class);
        }

        return $user;
    }

    private function forgetFeature(?User $user): void
    {
        if ($user !== null) {
            Feature::for($user)->forget(Notifications::class);
        }
    }

    private function cleanUp(?User ...$users): void
    {
        NotificationMessage::query()->where('created_by', self::ADMIN_USER_ID)->get()
            ->each(function (NotificationMessage $notificationMessage): void {
                UserNotification::query()->where('notification_message_id', $notificationMessage->id)->delete();
                GlobalNotificationRead::query()->where('notification_message_id', $notificationMessage->id)->delete();
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
