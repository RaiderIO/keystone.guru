<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\Laratrust\Role;
use App\Models\Notification\Enums\NotificationAudience;
use App\Models\Notification\GlobalNotificationRead;
use App\Models\Notification\NotificationMessage;
use App\Models\Notification\UserNotification;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
#[Group('Notification')]
final class AdminToolsNotificationControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID     = 1;
    private const int NON_ADMIN_USER_ID = 4;

    #[Test]
    public function index_givenAdmin_returnsOk(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $notificationMessage = null;

        try {
            $notificationMessage = NotificationMessage::factory()->create([
                'body'       => 'Rendered by test',
                'created_by' => self::ADMIN_USER_ID,
            ]);

            // Act
            $response = $this->get(route('admin.tools.notifications.view'));

            // Assert
            $response->assertOk();
            $response->assertSee('Rendered by test');
            $response->assertSee(route('admin.tools.notifications.store'), false);
        } finally {
            $this->cleanUpMessages();
            self::assertNotNull($notificationMessage);
        }
    }

    #[Test]
    public function index_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.notifications.view'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function store_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.notifications.store'), [
            'body'     => 'Should never be sent',
            'audience' => 'everyone',
        ]);

        // Assert
        $response->assertForbidden();
        self::assertSame(0, NotificationMessage::query()->where('body', 'Should never be sent')->count());
    }

    /**
     * The hybrid storage in one assertion: a site-wide announcement is one row and no deliveries,
     * however many accounts exist.
     */
    #[Test]
    public function store_givenEveryoneAudience_createsOneGlobalMessageWithoutDeliveries(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        try {
            // Act
            $response = $this->post(route('admin.tools.notifications.store'), [
                'body'     => 'A site-wide announcement',
                'audience' => 'everyone',
            ]);

            // Assert
            $response->assertRedirect(route('admin.tools.notifications.view'));
            $notificationMessage = NotificationMessage::query()->where('body', 'A site-wide announcement')->sole();
            self::assertSame(NotificationAudience::Global, $notificationMessage->audience);
            self::assertSame(0, UserNotification::query()->where('notification_message_id', $notificationMessage->id)->count());
        } finally {
            $this->cleanUpMessages();
        }
    }

    #[Test]
    public function store_givenUserNames_createsOneDeliveryPerUser(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $firstUser  = null;
        $secondUser = null;

        try {
            $firstUser  = $this->createUser();
            $secondUser = $this->createUser();

            // Act
            $response = $this->post(route('admin.tools.notifications.store'), [
                'body'       => 'For two users',
                'audience'   => 'users',
                'user_names' => sprintf('%s, %s', $firstUser->name, $secondUser->name),
            ]);

            // Assert
            $response->assertRedirect(route('admin.tools.notifications.view'));
            $notificationMessage = NotificationMessage::query()->where('body', 'For two users')->sole();
            self::assertSame(NotificationAudience::User, $notificationMessage->audience);
            self::assertSame(2, UserNotification::query()->where('notification_message_id', $notificationMessage->id)->count());
        } finally {
            $this->cleanUpMessages($firstUser, $secondUser);
        }
    }

    #[Test]
    public function store_givenRoleAudience_createsDeliveriesOnlyForThatRole(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $betaTester = null;
        $plainUser  = null;

        try {
            $betaTester = $this->createUser(Role::ROLE_BETA_TESTER);
            $plainUser  = $this->createUser();

            // Act
            $response = $this->post(route('admin.tools.notifications.store'), [
                'body'     => 'For beta testers',
                'audience' => 'role',
                'role'     => Role::ROLE_BETA_TESTER,
            ]);

            // Assert
            $response->assertRedirect(route('admin.tools.notifications.view'));
            $notificationMessage = NotificationMessage::query()->where('body', 'For beta testers')->sole();
            self::assertSame(1, UserNotification::query()
                ->where('notification_message_id', $notificationMessage->id)
                ->where('user_id', $betaTester->id)
                ->count());
            self::assertSame(0, UserNotification::query()
                ->where('notification_message_id', $notificationMessage->id)
                ->where('user_id', $plainUser->id)
                ->count());
        } finally {
            $this->cleanUpMessages($betaTester, $plainUser);
        }
    }

    #[Test]
    public function store_givenUnknownUserName_returnsValidationErrorAndSendsNothing(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        try {
            // Act
            $response = $this->post(route('admin.tools.notifications.store'), [
                'body'       => 'Never sent',
                'audience'   => 'users',
                'user_names' => 'ThisUserDoesNotExistAnywhere',
            ]);

            // Assert
            $response->assertSessionHasErrors(['user_names.0']);
            self::assertSame(0, NotificationMessage::query()->where('body', 'Never sent')->count());
        } finally {
            $this->cleanUpMessages();
        }
    }

    #[Test]
    public function store_givenAbsoluteUrl_returnsValidationErrorAndSendsNothing(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        try {
            // Act
            $response = $this->post(route('admin.tools.notifications.store'), [
                'body'     => 'Never sent',
                'audience' => 'everyone',
                'url'      => 'https://evil.example.com',
            ]);

            // Assert
            $response->assertSessionHasErrors(['url']);
            self::assertSame(0, NotificationMessage::query()->where('body', 'Never sent')->count());
        } finally {
            $this->cleanUpMessages();
        }
    }

    #[Test]
    public function store_givenBodyWithHtml_storesItAsPlainText(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        try {
            // Act
            $this->post(route('admin.tools.notifications.store'), [
                'body'     => 'Hello <script>alert(1)</script>world',
                'audience' => 'everyone',
            ]);

            // Assert
            $notificationMessage = NotificationMessage::query()->whereNotNull('body')->orderByDesc('id')->sole();
            self::assertStringNotContainsString('<script>', (string)$notificationMessage->body);
            self::assertStringContainsString('Hello', (string)$notificationMessage->body);
        } finally {
            $this->cleanUpMessages();
        }
    }

    #[Test]
    public function destroy_givenAdmin_retractsTheNotificationAndItsDeliveries(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $user = null;

        try {
            $user                = $this->createUser();
            $notificationMessage = NotificationMessage::factory()->create(['created_by' => self::ADMIN_USER_ID]);
            UserNotification::query()->create([
                'user_id'                 => $user->id,
                'notification_message_id' => $notificationMessage->id,
            ]);
            $notificationMessageId = $notificationMessage->id;

            // Act
            $response = $this->delete(route('admin.tools.notifications.destroy', ['notificationMessage' => $notificationMessageId]));

            // Assert
            $response->assertRedirect(route('admin.tools.notifications.view'));
            self::assertNull(NotificationMessage::query()->find($notificationMessageId));
            self::assertSame(0, UserNotification::query()->where('notification_message_id', $notificationMessageId)->count());
        } finally {
            $this->cleanUpMessages($user);
        }
    }

    private function createUser(?string $role = Role::ROLE_USER): User
    {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->addRole(Role::firstWhere('name', $role));
        }

        return $user;
    }

    private function cleanUpMessages(?User ...$users): void
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
