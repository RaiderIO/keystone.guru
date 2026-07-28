<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession\LiveSession;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('LiveSession')]
final class AjaxLiveSessionControllerTest extends AjaxPublicTestCase
{
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
