<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * Sorting /ajax/routes on each of its sortable columns. Every column handler adds its own joins, selects or
 * correlated subqueries to a query grouped by route, which MySQL's ONLY_FULL_GROUP_BY rejects unless each
 * expression is grouped, aggregated or functionally dependent on the route.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerSortTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    #[DataProvider('sortableColumnProvider')]
    public function get_givenOrderBySortableColumn_returnsTheRoutes(string $columnName, string $direction): void
    {
        // Arrange
        $user   = null;
        $routes = [];

        try {
            $user                 = $this->createUserWithUserRole();
            [$lowest, , $highest] = $this->getThreeAffixGroups();
            $this->bindCurrentAffixGroup($highest);
            $routes[] = $this->createOwnRoute($user, [$highest]);
            $routes[] = $this->createOwnRoute($user, [$lowest]);
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery($columnName, $direction));

            // Assert
            $response->assertOk();
            $this->assertSame(2, $response->json('recordsTotal'));
            $this->assertEqualsCanonicalizing(
                array_map(static fn(DungeonRoute $route): string => $route->public_key, $routes),
                array_column($response->json('data'), 'public_key'),
            );
        } finally {
            $this->deleteRoutes($routes);
            $user?->delete();
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function sortableColumnProvider(): array
    {
        $result = [];
        foreach (['title', 'dungeon_id', 'affixes.id', 'routeattributes.name', 'author.name', 'enemy_forces', 'views', 'rating'] as $columnName) {
            foreach (['asc', 'desc'] as $direction) {
                $result[sprintf('%s %s', $columnName, $direction)] = [$columnName, $direction];
            }
        }

        return $result;
    }

    #[Test]
    public function get_givenOrderByAffixesAscending_listsTheRouteWithTheCurrentAffixGroupFirst(): void
    {
        // Arrange - the route holding the current affix group also holds a lower one than the other route's, so
        // only a check across all of its affix groups puts it first
        $user   = null;
        $routes = [];

        try {
            $user                        = $this->createUserWithUserRole();
            [$lowest, $middle, $highest] = $this->getThreeAffixGroups();
            $this->bindCurrentAffixGroup($highest);
            $currentAffixGroupRoute = $this->createOwnRoute($user, [$middle, $highest]);
            $otherRoute             = $this->createOwnRoute($user, [$lowest]);
            $routes                 = [$currentAffixGroupRoute, $otherRoute];
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery('affixes.id', 'asc'));

            // Assert
            $response->assertOk();
            $this->assertSame(
                [$currentAffixGroupRoute->public_key, $otherRoute->public_key],
                array_column($response->json('data'), 'public_key'),
            );
        } finally {
            $this->deleteRoutes($routes);
            $user?->delete();
        }
    }

    /**
     * @return array{AffixGroup, AffixGroup, AffixGroup} three seeded affix groups, lowest id first
     */
    private function getThreeAffixGroups(): array
    {
        $affixGroups = AffixGroup::query()->orderBy('id')->limit(3)->get();
        $this->assertCount(3, $affixGroups, 'The seeded database must hold at least three affix groups');

        return [$affixGroups[0], $affixGroups[1], $affixGroups[2]];
    }

    /**
     * The affixes column only sorts when there is a current affix group, which the seed does not promise.
     */
    private function bindCurrentAffixGroup(AffixGroup $affixGroup): void
    {
        $seasonAffixGroupService = $this->createMock(SeasonAffixGroupServiceInterface::class);
        $seasonAffixGroupService->method('getCurrentAffixGroup')->willReturn($affixGroup);
        $this->app->instance(SeasonAffixGroupServiceInterface::class, $seasonAffixGroupService);
    }

    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /**
     * @param array<int, AffixGroup> $affixGroups
     */
    private function createOwnRoute(User $user, array $affixGroups): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        foreach ($affixGroups as $affixGroup) {
            DungeonRouteAffixGroup::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'affix_group_id'   => $affixGroup->id,
            ]);
        }

        return $dungeonRoute;
    }

    /**
     * @param array<int, DungeonRoute> $routes
     */
    private function deleteRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            DungeonRouteAffixGroup::query()->where('dungeon_route_id', $route->id)->delete();
            $route->delete();
        }
    }

    private function mineQuery(string $columnName, string $direction): string
    {
        return sprintf('/ajax/routes?%s', http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 'title',
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data'       => $columnName,
                    'name'       => $columnName,
                    'searchable' => 'false',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'order'  => [['column' => $columnName === 'title' ? 0 : 1, 'dir' => $direction]],
            'search' => ['value' => '', 'regex' => 'false'],
            'mine'   => 1,
        ]));
    }
}
