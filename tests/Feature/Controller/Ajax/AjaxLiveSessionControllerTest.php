<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Laratrust\Role;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('LiveSession')]
final class AjaxLiveSessionControllerTest extends AjaxPublicTestCase
{
    use GeneratesDungeonRoutes;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    #[Test]
    public function delete_givenAnotherUsersRoute_returnsForbidden(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $nonOwner    = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSession($owner);

        try {
            $this->be($nonOwner);

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert - the session must still be running
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
            $nonOwner->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenOwnRoute_endsTheLiveSession(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSession($owner);

        try {
            $this->be($owner);

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['expires_in']);
            $this->assertNotNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenAlreadyEndedSession_keepsItsExpiryAndReturnsTheTimeLeft(): void
    {
        // Arrange
        $this->freezeSecond();
        $owner       = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSession($owner);
        $expiresAt   = now()->addMinutes(10);
        LiveSession::query()->whereKey($liveSession->id)->update(['expires_at' => $expiresAt]);

        try {
            $this->be($owner);

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert
            $response->assertOk();
            $response->assertJsonPath('expires_in', 600);
            $this->assertEquals($expiresAt->toDateTimeString(), Carbon::parse($liveSession->fresh()->expires_at)->toDateTimeString());
        } finally {
            $this->deleteLiveSession($liveSession);
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenRouteOwnerWhoIsNotTheSessionCreator_returnsForbidden(): void
    {
        // Arrange - a live session may be started on any route its creator can view, not just their
        // own, so the route owner being able to edit their own route must not let them end a session
        // someone else started on it; only the session's own creator (or an admin) may end it
        $routeOwner     = $this->createUserWithUserRole();
        $sessionCreator = $this->createUserWithUserRole();
        $liveSession    = $this->createLiveSession($routeOwner, $sessionCreator);

        try {
            $this->be($routeOwner);

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert - the session must still be running
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
            $sessionCreator->delete();
            $routeOwner->delete();
        }
    }

    #[Test]
    public function delete_givenSessionCreatorIsNotRouteOwner_endsTheLiveSession(): void
    {
        // Arrange - the session's creator is neither the route owner nor a collaborator, so the
        // route's edit gate alone would deny them; they must still be able to end their own session
        $routeOwner     = $this->createUserWithUserRole();
        $sessionCreator = $this->createUserWithUserRole();
        $liveSession    = $this->createLiveSession($routeOwner, $sessionCreator);

        try {
            $this->be($sessionCreator);

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['expires_in']);
            $this->assertNotNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
            $sessionCreator->delete();
            $routeOwner->delete();
        }
    }

    #[Test]
    public function delete_givenAdminWhoIsNotTheSessionCreator_endsTheLiveSession(): void
    {
        // Arrange - LiveSessionPolicy::end() explicitly allows an admin regardless of who started
        // the session. AjaxPublicTestCase already acts as the seeded admin (id=1) by default, so
        // there is nothing to $this->be() here - just don't override it like the other tests do.
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        $routeOwner     = $this->createUserWithUserRole();
        $sessionCreator = $this->createUserWithUserRole();
        $liveSession    = $this->createLiveSession($routeOwner, $sessionCreator);

        try {
            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['expires_in']);
            $this->assertNotNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
            $sessionCreator->delete();
            $routeOwner->delete();
        }
    }

    // -------------------------------------------------------------------------
    // state
    // -------------------------------------------------------------------------

    #[Test]
    public function state_givenAViewerOfAnotherUsersPublishedRoute_returnsTheStateTheMapContextCarries(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $viewer      = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::WORLD);

        try {
            $enemies = $this->uniquelyMatchedEnemies($liveSession->dungeonRoute, 2);
            $this->assertCount(2, $enemies, 'The route needs two enemies with a unique npc_id/mdt_id pair');
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::Killed, $enemies[0]);
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::InCombat, $enemies[1]);

            $mapContextState = app(MapContextServiceInterface::class)
                ->createMapContextLiveSession($liveSession, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
                ->toArray()['liveSessionState'];
            $this->be($viewer);

            // Act
            $response = $this->get($this->stateUrl($liveSession));

            // Assert
            $response->assertOk();
            $this->assertSame([$enemies[0]->id], $response->json('killed'));
            $this->assertSame([$enemies[1]->id], $response->json('in_combat'));
            $this->assertSame($mapContextState, $response->json());
            $this->assertSame(app(LiveSessionStateServiceInterface::class)->getState($liveSession), $response->json());
        } finally {
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $this->deleteLiveSession($liveSession);
            $viewer->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function state_givenAnUnpublishedRouteOfAnotherUser_returnsForbidden(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $nonViewer   = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::UNPUBLISHED);

        try {
            $this->be($nonViewer);

            // Act
            $response = $this->get($this->stateUrl($liveSession));

            // Assert
            $response->assertForbidden();
        } finally {
            $this->deleteLiveSession($liveSession);
            $nonViewer->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function state_givenTheOwnerOfAnUnpublishedRoute_returnsTheState(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::UNPUBLISHED);

        try {
            $this->be($owner);

            // Act
            $response = $this->get($this->stateUrl($liveSession));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['killed', 'in_combat', 'obsolete', 'overpulled', 'players']);
        } finally {
            $this->deleteLiveSession($liveSession);
            $owner->delete();
        }
    }

    #[Test]
    public function state_givenALiveSessionOfAnotherRoute_returnsNotFound(): void
    {
        // Arrange
        $owner        = $this->createUserWithUserRole();
        $liveSession  = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::WORLD);
        $otherSession = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::WORLD);

        try {
            $this->be($owner);

            // Act
            $response = $this->get(sprintf(
                '/ajax/%s/live/%s/state',
                $otherSession->dungeonRoute->public_key,
                $liveSession->public_key,
            ));

            // Assert
            $response->assertNotFound();
        } finally {
            $this->deleteLiveSession($otherSession);
            $this->deleteLiveSession($liveSession);
            $owner->delete();
        }
    }

    #[Test]
    public function state_givenAnExpiredLiveSession_returnsForbidden(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::WORLD);
        LiveSession::query()->whereKey($liveSession->id)->update(['expires_at' => now()->subMinute()]);

        try {
            $this->be($owner);

            // Act
            $response = $this->get($this->stateUrl($liveSession));

            // Assert
            $response->assertForbidden();
        } finally {
            $this->deleteLiveSession($liveSession);
            $owner->delete();
        }
    }

    #[Test]
    public function state_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $owner       = $this->createUserWithUserRole();
        $liveSession = $this->createLiveSessionOnARouteWithEnemies($owner, PublishedState::WORLD);

        try {
            $this->app['auth']->guard()->logout();

            // Act
            $response = $this->getJson($this->stateUrl($liveSession));

            // Assert
            $response->assertUnauthorized();
        } finally {
            $this->deleteLiveSession($liveSession);
            $owner->delete();
        }
    }

    /**
     * The delete route sits behind 'role:user|admin', so an actor without the "user" role would be
     * rejected by the role middleware before the policy ever runs - masking what these tests check.
     * Not an admin, since an admin may edit every route.
     */
    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        return $user;
    }

    /**
     * Creates a running live session on a published, non-sandbox route owned by $owner. Sandbox
     * routes are editable by anyone by design, so expires_at must be null here. Pass $sessionCreator
     * to give the session a creator distinct from the route owner (defaults to $owner).
     */
    private function createLiveSession(User $owner, ?User $sessionCreator = null): LiveSession
    {
        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        return LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => ($sessionCreator ?? $owner)->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
    }

    private function createLiveSessionOnARouteWithEnemies(User $owner, string $publishedState): LiveSession
    {
        $route = $this->createNonFacadeDungeonRouteWithEnemies();
        DungeonRoute::query()->whereKey($route->id)->update([
            'author_id'          => $owner->id,
            'published_state_id' => PublishedState::ALL[$publishedState],
            'expires_at'         => null,
        ]);

        return LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $owner->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
    }

    /**
     * @return Collection<int, Enemy>
     */
    private function uniquelyMatchedEnemies(DungeonRoute $dungeonRoute, int $count): Collection
    {
        return Enemy::query()
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->orderBy('id')
            ->get()
            ->groupBy(static fn(Enemy $enemy): string => sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id))
            ->filter(static fn(Collection $enemies): bool => $enemies->count() === 1)
            ->flatten(1)
            ->take($count)
            ->values();
    }

    private function storeEnemyState(LiveSession $liveSession, LiveSessionEnemyStateType $state, Enemy $enemy): void
    {
        LiveSessionEnemyState::query()->create([
            'live_session_id' => $liveSession->id,
            'state'           => $state,
            'npc_id'          => $enemy->npc_id,
            'mdt_id'          => $enemy->mdt_id,
        ]);
    }

    private function stateUrl(LiveSession $liveSession): string
    {
        return sprintf(
            '/ajax/%s/live/%s/state',
            $liveSession->dungeonRoute->public_key,
            $liveSession->public_key,
        );
    }

    private function deleteUrl(LiveSession $liveSession): string
    {
        return sprintf(
            '/ajax/%s/live/%s',
            $liveSession->dungeonRoute->public_key,
            $liveSession->public_key,
        );
    }

    private function deleteLiveSession(LiveSession $liveSession): void
    {
        $route = $liveSession->dungeonRoute;

        // Mass delete on purpose: LiveSession's "deleting" hook cascades into overpulled_enemies,
        // a table no migration creates, so $liveSession->delete() throws on a migrated database.
        LiveSession::query()->whereKey($liveSession->id)->delete();
        $route?->delete();
    }
}
