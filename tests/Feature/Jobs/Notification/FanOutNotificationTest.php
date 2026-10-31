<?php

namespace Tests\Feature\Jobs\Notification;

use App\Jobs\Enums\QueueName;
use App\Jobs\Notification\FanOutNotification;
use App\Models\Laratrust\Role;
use App\Models\Notification\NotificationMessage;
use App\Models\Notification\UserNotification;
use App\Models\User;
use App\Service\Notification\NotificationTarget;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('Notification')]
final class FanOutNotificationTest extends PublicTestCase
{
    private const int ADMIN_USER_ID = 1;

    #[Test]
    public function handle_givenRoleTarget_deliversOnlyToUsersWithThatRole(): void
    {
        // Arrange
        $betaTester = null;
        $plainUser  = null;

        try {
            $betaTester          = $this->createUser(Role::ROLE_BETA_TESTER);
            $plainUser           = $this->createUser(Role::ROLE_USER);
            $notificationMessage = NotificationMessage::factory()->create(['created_by' => self::ADMIN_USER_ID]);

            // Act
            app()->call([new FanOutNotification($notificationMessage->id, NotificationTarget::role(Role::ROLE_BETA_TESTER)), 'handle']);

            // Assert
            self::assertSame(1, UserNotification::query()
                ->where('notification_message_id', $notificationMessage->id)
                ->where('user_id', $betaTester->id)
                ->count());
            self::assertSame(0, UserNotification::query()
                ->where('notification_message_id', $notificationMessage->id)
                ->where('user_id', $plainUser->id)
                ->count());
        } finally {
            $this->cleanUp($betaTester, $plainUser);
        }
    }

    /**
     * A failed fan-out is retried from the start, so re-running it must not notify anybody twice.
     */
    #[Test]
    public function handle_givenASecondRun_doesNotDuplicateDeliveries(): void
    {
        // Arrange
        $user = null;

        try {
            $user                = $this->createUser(Role::ROLE_BETA_TESTER);
            $notificationMessage = NotificationMessage::factory()->create(['created_by' => self::ADMIN_USER_ID]);
            $job                 = new FanOutNotification($notificationMessage->id, NotificationTarget::userIds([$user->id]));
            app()->call([$job, 'handle']);

            // Act
            app()->call([$job, 'handle']);

            // Assert
            self::assertSame(1, UserNotification::query()
                ->where('notification_message_id', $notificationMessage->id)
                ->count());
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function handle_givenADeletedMessage_doesNothing(): void
    {
        // Arrange
        $user = null;

        try {
            $user                  = $this->createUser(Role::ROLE_USER);
            $notificationMessage   = NotificationMessage::factory()->create(['created_by' => self::ADMIN_USER_ID]);
            $notificationMessageId = $notificationMessage->id;
            $notificationMessage->delete();

            // Act
            app()->call([new FanOutNotification($notificationMessageId, NotificationTarget::userIds([$user->id])), 'handle']);

            // Assert
            self::assertSame(0, UserNotification::query()->where('notification_message_id', $notificationMessageId)->count());
        } finally {
            $this->cleanUp($user);
        }
    }

    /**
     * Reusing an existing queue is deliberate: every QueueName case is its own SQS queue and ECS
     * worker in the infrastructure repository.
     */
    #[Test]
    public function construct_putsTheJobOnTheLongRunningQueue(): void
    {
        // Act
        $job = new FanOutNotification(1, NotificationTarget::everyone());

        // Assert
        self::assertSame(QueueName::LongRunning->queueName(), $job->queue);
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', $role));

        return $user;
    }

    private function cleanUp(?User ...$users): void
    {
        NotificationMessage::query()->where('created_by', self::ADMIN_USER_ID)->get()
            ->each(function (NotificationMessage $notificationMessage): void {
                UserNotification::query()->where('notification_message_id', $notificationMessage->id)->delete();
                $notificationMessage->delete();
            });

        foreach ($users as $user) {
            if ($user !== null) {
                UserNotification::query()->where('user_id', $user->id)->delete();
                $user->delete();
            }
        }
    }
}
