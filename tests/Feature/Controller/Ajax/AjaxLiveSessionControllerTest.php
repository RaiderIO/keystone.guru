<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
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
     * routes are editable by anyone by design, so expires_at must be null here.
     */
    private function createLiveSession(User $owner): LiveSession
    {
        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        return LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $owner->id,
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
