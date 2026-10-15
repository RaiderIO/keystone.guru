<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('UserReport')]
final class AjaxUserReportControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function dungeonrouteStore_givenRouteUserMayNotView_returnsForbidden(): void
    {
        // Arrange
        $reporter     = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::UNPUBLISHED);

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->post(sprintf('/ajax/userreport/dungeonroute/%s', $dungeonRoute->public_key), $this->validPayload());

            // Assert
            $response->assertForbidden();
            $this->assertSame(0, $this->reportsFor($dungeonRoute)->count());
        } finally {
            $this->reportsFor($dungeonRoute)->delete();
            $dungeonRoute->delete();
            $reporter->delete();
        }
    }

    #[Test]
    public function dungeonrouteStore_givenRouteUserMayView_createsTheReport(): void
    {
        // Arrange
        $reporter     = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->post(sprintf('/ajax/userreport/dungeonroute/%s', $dungeonRoute->public_key), $this->validPayload());

            // Assert
            $response->assertNoContent();
            $this->assertSame(1, $this->reportsFor($dungeonRoute)->where('user_id', $reporter->id)->count());
        } finally {
            $this->reportsFor($dungeonRoute)->delete();
            $dungeonRoute->delete();
            $reporter->delete();
        }
    }

    #[Test]
    public function dungeonrouteStore_givenNoMessage_returnsValidationErrorAndStoresNothing(): void
    {
        // Arrange
        $reporter     = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->postJson(sprintf('/ajax/userreport/dungeonroute/%s', $dungeonRoute->public_key), [
                'category' => 'other',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['message']);
            $this->assertSame(0, $this->reportsFor($dungeonRoute)->count());
        } finally {
            $this->reportsFor($dungeonRoute)->delete();
            $dungeonRoute->delete();
            $reporter->delete();
        }
    }

    #[Test]
    public function dungeonrouteStore_givenGuest_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);

        try {
            $this->actingAsGuest();

            // Act
            $response = $this->post(sprintf('/ajax/userreport/dungeonroute/%s', $dungeonRoute->public_key), $this->validPayload());

            // Assert
            $response->assertForbidden();
            $this->assertSame(0, $this->reportsFor($dungeonRoute)->count());
        } finally {
            $this->reportsFor($dungeonRoute)->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function dungeonrouteStore_givenUserWithoutName_returnsNoNameValidationError(): void
    {
        // Arrange
        $reporter     = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->postJson(sprintf('/ajax/userreport/dungeonroute/%s', $dungeonRoute->public_key), [
                'category' => 'other',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['message']);
            $response->assertJsonMissingValidationErrors(['name']);
        } finally {
            $this->reportsFor($dungeonRoute)->delete();
            $dungeonRoute->delete();
            $reporter->delete();
        }
    }

    #[Test]
    public function enemyStore_givenValidPayload_createsTheReport(): void
    {
        // Arrange
        $reporter = $this->createUserWithUserRole();
        /** @var Enemy $enemy */
        $enemy = Enemy::query()->firstOrFail();

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->post(sprintf('/ajax/userreport/enemy/%s', $enemy->id), $this->validPayload());

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseHas(UserReport::class, [
                'model_class' => Enemy::class,
                'model_id'    => $enemy->id,
                'user_id'     => $reporter->id,
                'category'    => 'other',
                'message'     => 'Something is off with this route',
            ]);
        } finally {
            UserReport::query()->where('user_id', $reporter->id)->delete();
            $reporter->delete();
        }
    }

    #[Test]
    public function enemyStore_givenGuest_returnsForbidden(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy        = Enemy::query()->firstOrFail();
        $reportsQuery = UserReport::query()
            ->where('model_class', Enemy::class)
            ->where('model_id', $enemy->id);
        $reportsBefore = $reportsQuery->count();

        try {
            $this->actingAsGuest();

            // Act
            $response = $this->post(sprintf('/ajax/userreport/enemy/%s', $enemy->id), $this->validPayload());

            // Assert
            $response->assertForbidden();
            $this->assertSame($reportsBefore, $reportsQuery->clone()->count());
        } finally {
            $reportsQuery->clone()->where('message', $this->validPayload()['message'])->delete();
        }
    }

    #[Test]
    public function enemyStore_givenUsername_storesTheReportWithoutIt(): void
    {
        // Arrange
        $reporter = $this->createUserWithUserRole();
        /** @var Enemy $enemy */
        $enemy = Enemy::query()->firstOrFail();

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->post(sprintf('/ajax/userreport/enemy/%s', $enemy->id), array_merge($this->validPayload(), [
                'username' => 'Someone else',
            ]));

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseHas(UserReport::class, [
                'model_class' => Enemy::class,
                'model_id'    => $enemy->id,
                'user_id'     => $reporter->id,
                'username'    => null,
            ]);
        } finally {
            UserReport::query()->where('user_id', $reporter->id)->delete();
            $reporter->delete();
        }
    }

    #[Test]
    public function status_givenAdmin_updatesTheReportStatus(): void
    {
        // Arrange
        $userReport = UserReport::create([
            'model_id'    => 1,
            'model_class' => Enemy::class,
            'user_id'     => 1,
            'category'    => 'other',
            'message'     => 'Created by AjaxUserReportControllerTest',
            'contact_ok'  => false,
            'status'      => 0,
        ]);

        try {
            // Act
            $response = $this->put(sprintf('/ajax/userreport/%s/status', $userReport->id), [
                'status' => 1,
            ]);

            // Assert
            $response->assertOk();
            $this->assertEquals(1, $userReport->fresh()->status);
        } finally {
            $userReport->delete();
        }
    }

    #[Test]
    public function status_givenAnEmptyStatus_resetsTheReportToOpen(): void
    {
        // Arrange
        $userReport = UserReport::create([
            'model_id'    => 1,
            'model_class' => Enemy::class,
            'user_id'     => 1,
            'category'    => 'other',
            'message'     => 'Created by AjaxUserReportControllerTest',
            'contact_ok'  => false,
            'status'      => 1,
        ]);

        try {
            // Act
            $response = $this->put(sprintf('/ajax/userreport/%s/status', $userReport->id), [
                'status' => '',
            ]);

            // Assert
            $response->assertOk();
            $this->assertEquals(0, $userReport->fresh()->status);
        } finally {
            $userReport->delete();
        }
    }

    #[Test]
    public function dungeonrouteStore_givenTheLongestMessageAllowed_storesItWhole(): void
    {
        // Arrange
        $reporter     = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);
        $message      = str_repeat('a', 1000);

        try {
            $this->actingAs($reporter);

            // Act
            $response = $this->post(sprintf('/ajax/userreport/dungeonroute/%s', $dungeonRoute->public_key), [
                'category' => 'other',
                'message'  => $message,
            ]);

            // Assert
            $response->assertNoContent();
            $this->assertSame($message, $this->reportsFor($dungeonRoute)->where('user_id', $reporter->id)->firstOrFail()->message);
        } finally {
            $this->reportsFor($dungeonRoute)->delete();
            $dungeonRoute->delete();
            $reporter->delete();
        }
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'category' => 'other',
            'message'  => 'Something is off with this route',
        ];
    }

    /**
     * @return Builder<UserReport>
     */
    private function reportsFor(DungeonRoute $dungeonRoute): Builder
    {
        return UserReport::query()
            ->where('model_class', DungeonRoute::class)
            ->where('model_id', $dungeonRoute->id);
    }

    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /**
     * A non-sandbox route authored by user 1. Sandbox routes (expires_at set, which the factory does
     * by default) are viewable by anyone by design, so expires_at must be null for a
     * view-authorization assertion to mean anything.
     */
    private function createRouteOwnedByAnotherUser(string $publishedState): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[$publishedState],
            'expires_at'         => null,
        ]);
    }
}
