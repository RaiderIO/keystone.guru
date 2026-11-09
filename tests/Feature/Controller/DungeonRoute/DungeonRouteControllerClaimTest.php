<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteControllerClaimTest extends PublicTestCase
{
    #[Test]
    public function claim_givenPostRequestOnSandboxRoute_claimsTheRouteAndRedirectsToEdit(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            [$user, $route] = $this->createUserAndSandboxRoute();

            // Act
            $response = $this->actingAs($user)->post($this->claimUrl($route));

            // Assert
            $route->refresh();
            $this->assertSame($user->id, $route->author_id);
            $this->assertNull($route->expires_at);
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function claim_givenGetRequest_leavesTheSandboxRouteUnclaimed(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            [$user, $route] = $this->createUserAndSandboxRoute();

            // Act
            $this->actingAs($user)->get($this->claimUrl($route));

            // Assert - a GET falls through to the read-only view-floor route, which takes "claim" for a floor index
            $this->assertSame('dungeonroute.view.floor', Route::currentRouteName());
            $route->refresh();
            $this->assertSame(1, $route->author_id);
            $this->assertNotNull($route->expires_at);
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    /**
     * @return array{0: User, 1: DungeonRoute}
     */
    private function createUserAndSandboxRoute(): array
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $route = DungeonRoute::factory()->create([
            'author_id'  => 1,
            'expires_at' => now()->addHours(2),
        ]);

        return [$user, $route];
    }

    private function claimUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.claim', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
