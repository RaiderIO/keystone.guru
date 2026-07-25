<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
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
    /**
     * The delete route sits behind 'role:user|admin', so both actors must carry the "user" role -
     * a bare User::factory() user would be rejected by the role middleware and mask the policy.
     * Neither is an admin, since admins may edit every route.
     */
    private const int ROUTE_OWNER_USER_ID = 3;

    private const int OTHER_USER_ID = 4;

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
        $liveSession = $this->createLiveSession();

        try {
            $this->be(User::findOrFail(self::OTHER_USER_ID));

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert - the session must still be running
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
        }
    }

    #[Test]
    public function delete_givenOwnRoute_endsTheLiveSession(): void
    {
        // Arrange
        $liveSession = $this->createLiveSession();

        try {
            $this->be(User::findOrFail(self::ROUTE_OWNER_USER_ID));

            // Act
            $response = $this->delete($this->deleteUrl($liveSession));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['expires_in']);
            $this->assertNotNull($liveSession->fresh()->expires_at);
        } finally {
            $this->deleteLiveSession($liveSession);
        }
    }

    /**
     * Creates a running live session on a published, non-sandbox route owned by ROUTE_OWNER_USER_ID.
     * Sandbox routes are editable by anyone by design, so expires_at must be null here.
     */
    private function createLiveSession(): LiveSession
    {
        $route = DungeonRoute::factory()->create([
            'author_id'          => self::ROUTE_OWNER_USER_ID,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        return LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => self::ROUTE_OWNER_USER_ID,
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
