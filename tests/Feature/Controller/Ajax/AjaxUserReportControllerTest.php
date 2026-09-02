<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
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
