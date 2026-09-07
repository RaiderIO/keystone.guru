<?php

namespace Tests\Feature\Routes;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('Routes')]
#[Group('DungeonRoute')]
final class DungeonRouteChannelAuthorizationTest extends PublicTestCase
{
    #[Test]
    public function routeEditChannel_givenUserWhoMayNotViewTheRoute_returnsFalse(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $outsider = $this->createUserWithUserRole();
        $route    = $this->createRoute($owner, PublishedState::UNPUBLISHED);

        try {
            // Act
            $result = $this->getChannelCallback($this->routeEditChannel())($outsider, $route);

            // Assert
            $this->assertFalse($result);
        } finally {
            $route->delete();
            $outsider->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function routeEditChannel_givenTheAuthorOfAnUnpublishedRoute_returnsPresenceData(): void
    {
        // Arrange
        $owner = $this->createUserWithUserRole();
        $route = $this->createRoute($owner, PublishedState::UNPUBLISHED);

        try {
            // Act
            $result = $this->getChannelCallback($this->routeEditChannel())($owner, $route);

            // Assert
            $this->assertIsArray($result);
            $this->assertSame($owner->public_key, $result['public_key']);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function routeEditChannel_givenTeamMemberOfATeamPublishedRoute_returnsPresenceData(): void
    {
        // Arrange
        $owner  = User::factory()->create();
        $member = $this->createUserWithUserRole();
        $team   = null;
        $route  = null;

        try {
            $team = Team::create([
                'public_key'               => Team::generateRandomPublicKey(),
                'name'                     => 'Channel authorization team',
                'description'              => '',
                'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
                'default_role'             => TeamUser::ROLE_MEMBER,
                'route_publishing_enabled' => true,
            ]);
            TeamUser::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamUser::ROLE_ADMIN]);
            TeamUser::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamUser::ROLE_MEMBER]);

            $route = $this->createRoute($owner, PublishedState::TEAM, ['team_id' => $team->id]);

            // Act
            $result = $this->getChannelCallback($this->routeEditChannel())($member, $route);

            // Assert
            $this->assertIsArray($result);
            $this->assertSame($member->public_key, $result['public_key']);
        } finally {
            $route?->delete();
            if ($team !== null) {
                TeamUser::query()->where('team_id', $team->id)->delete();
                Team::query()->whereKey($team->id)->delete();
            }
            $member->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function liveSessionChannel_givenUserWhoMayNotViewTheRoute_returnsFalse(): void
    {
        // Arrange
        $owner       = User::factory()->create();
        $outsider    = $this->createUserWithUserRole();
        $route       = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $liveSession = $this->createLiveSession($route, $owner);

        try {
            // Act
            $result = $this->getChannelCallback($this->liveSessionChannel())($outsider, $liveSession);

            // Assert
            $this->assertFalse($result);
        } finally {
            LiveSession::query()->whereKey($liveSession->id)->delete();
            $route->delete();
            $outsider->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function liveSessionChannel_givenAnyUserOnAWorldPublishedRoute_returnsPresenceData(): void
    {
        // Arrange
        $owner       = User::factory()->create();
        $participant = $this->createUserWithUserRole();
        $route       = $this->createRoute($owner, PublishedState::WORLD);
        $liveSession = $this->createLiveSession($route, $owner);

        try {
            // Act
            $result = $this->getChannelCallback($this->liveSessionChannel())($participant, $liveSession);

            // Assert
            $this->assertIsArray($result);
            $this->assertSame($participant->public_key, $result['public_key']);
        } finally {
            LiveSession::query()->whereKey($liveSession->id)->delete();
            $route->delete();
            $participant->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function routeCompareChannel_givenOneRouteTheUserMayNotView_returnsFalse(): void
    {
        // Arrange
        $owner     = User::factory()->create();
        $outsider  = $this->createUserWithUserRole();
        $published = $this->createRoute($owner, PublishedState::WORLD);
        $draft     = $this->createRoute($owner, PublishedState::UNPUBLISHED);

        try {
            // Act
            $result = $this->getChannelCallback($this->routeCompareChannel())($outsider, $published, $draft);

            // Assert
            $this->assertFalse($result);
        } finally {
            $draft->delete();
            $published->delete();
            $outsider->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function routeCompareChannel_givenTwoWorldPublishedRoutes_returnsTrue(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $outsider = $this->createUserWithUserRole();
        $routeA   = $this->createRoute($owner, PublishedState::WORLD);
        $routeB   = $this->createRoute($owner, PublishedState::WORLD);

        try {
            // Act
            $result = $this->getChannelCallback($this->routeCompareChannel())($outsider, $routeA, $routeB);

            // Assert
            $this->assertTrue($result);
        } finally {
            $routeB->delete();
            $routeA->delete();
            $outsider->delete();
            $owner->delete();
        }
    }

    private function routeEditChannel(): string
    {
        return sprintf('%s-route-edit.{dungeonRoute}', config('app.type'));
    }

    private function liveSessionChannel(): string
    {
        return sprintf('%s-live-session.{liveSession}', config('app.type'));
    }

    private function routeCompareChannel(): string
    {
        return sprintf('%s-route-compare.{dungeonRouteA}-{dungeonRouteB}', config('app.type'));
    }

    /**
     * The configured broadcast driver in tests is `log`, whose auth() is a no-op - so the callbacks
     * routes/channels.php registers cannot be reached over /broadcasting/auth and are invoked
     * directly here instead, with the route model binding already resolved.
     */
    private function getChannelCallback(string $channelName): callable
    {
        /** @var array<string, callable> $channels */
        $channels = new ReflectionProperty(Broadcaster::class, 'channels')->getValue(Broadcast::driver());

        $this->assertArrayHasKey($channelName, $channels, sprintf('Channel %s is not registered', $channelName));

        return $channels[$channelName];
    }

    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRoute(User $owner, string $publishedState, array $overrides = []): DungeonRoute
    {
        return DungeonRoute::factory()->create(array_merge([
            'author_id' => $owner->id,
            // Sandbox routes (expires_at set, which the factory does by default) are viewable by
            // anyone by design, so a view assertion on one would mean nothing
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ], $overrides));
    }

    private function createLiveSession(DungeonRoute $dungeonRoute, User $creator): LiveSession
    {
        return LiveSession::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $creator->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
    }
}
