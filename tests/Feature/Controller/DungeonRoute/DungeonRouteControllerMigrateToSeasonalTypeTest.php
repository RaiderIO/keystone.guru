<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteControllerMigrateToSeasonalTypeTest extends PublicTestCase
{
    private const string SEASONAL_TYPE = 'encrypted';

    #[Test]
    public function migrateToSeasonalType_givenPostRequest_replacesTheAffixGroupsAndRedirectsToEdit(): void
    {
        // Arrange
        $owner = null;
        $route = null;

        try {
            [$owner, $route, $dungeonRouteAffixGroup] = $this->createOwnerAndRouteWithAffixGroup();

            // Act
            $response = $this->actingAs($owner)->post($this->migrateUrl($route));

            // Assert
            $this->assertFalse(DungeonRouteAffixGroup::query()->whereKey($dungeonRouteAffixGroup->id)->exists());
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
        } finally {
            $this->cleanup($owner, $route);
        }
    }

    #[Test]
    public function migrateToSeasonalType_givenGetRequest_returnsMethodNotAllowedAndKeepsTheAffixGroups(): void
    {
        // Arrange
        $owner = null;
        $route = null;

        try {
            [$owner, $route, $dungeonRouteAffixGroup] = $this->createOwnerAndRouteWithAffixGroup();

            // Act
            $response = $this->actingAs($owner)->get($this->migrateUrl($route));

            // Assert
            $response->assertMethodNotAllowed();
            $this->assertTrue(DungeonRouteAffixGroup::query()->whereKey($dungeonRouteAffixGroup->id)->exists());
        } finally {
            $this->cleanup($owner, $route);
        }
    }

    /**
     * @return array{0: User, 1: DungeonRoute, 2: DungeonRouteAffixGroup}
     */
    private function createOwnerAndRouteWithAffixGroup(): array
    {
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        $dungeonRouteAffixGroup = DungeonRouteAffixGroup::create([
            'dungeon_route_id' => $route->id,
            'affix_group_id'   => AffixGroup::query()->orderBy('id')->firstOrFail()->id,
        ]);

        return [$owner, $route, $dungeonRouteAffixGroup];
    }

    private function migrateUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.migrate', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
            'seasonalType' => self::SEASONAL_TYPE,
        ]);
    }

    private function cleanup(?User $owner, ?DungeonRoute $route): void
    {
        if ($route !== null) {
            DungeonRouteAffixGroup::query()->where('dungeon_route_id', $route->id)->delete();
            $route->delete();
        }

        $owner?->delete();
    }
}
