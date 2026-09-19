<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The scope constraints of /ajax/routes (game version, season, dungeons) that a route picker locks.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerListScopeTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function get_givenGameVersionIdOfTheRoutesMappingVersion_returnsTheRoute(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user  = $this->createUserWithUserRole();
            $route = $this->createOwnRoute($user);
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery(['game_version_id' => $route->mappingVersion->game_version_id]));

            // Assert
            $response->assertOk();
            $this->assertSame([$route->public_key], array_column($response->json('data'), 'public_key'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenAnotherGameVersionId_returnsNoRoutes(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user  = $this->createUserWithUserRole();
            $route = $this->createOwnRoute($user);
            /** @var GameVersion $otherGameVersion */
            $otherGameVersion = GameVersion::query()
                ->where('id', '!=', $route->mappingVersion->game_version_id)
                ->firstOrFail();
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery(['game_version_id' => $otherGameVersion->id]));

            // Assert
            $response->assertOk();
            $this->assertSame([], $response->json('data'));
            $this->assertSame(0, $response->json('recordsFiltered'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenSeasonIdOfTheRoute_returnsTheRoute(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user = $this->createUserWithUserRole();
            /** @var Season $season */
            $season = Season::query()->firstOrFail();
            $route  = $this->createOwnRoute($user, ['season_id' => $season->id]);
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery(['season_id' => $season->id]));

            // Assert
            $response->assertOk();
            $this->assertSame([$route->public_key], array_column($response->json('data'), 'public_key'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenAnotherSeasonId_returnsNoRoutes(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user = $this->createUserWithUserRole();
            /** @var Season $season */
            $season = Season::query()->firstOrFail();
            /** @var Season $otherSeason */
            $otherSeason = Season::query()->where('id', '!=', $season->id)->firstOrFail();
            $route       = $this->createOwnRoute($user, ['season_id' => $season->id]);
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery(['season_id' => $otherSeason->id]));

            // Assert
            $response->assertOk();
            $this->assertSame([], $response->json('data'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenDungeonIdsContainingTheRoutesDungeon_returnsTheRoute(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user  = $this->createUserWithUserRole();
            $route = $this->createOwnRoute($user);
            /** @var Dungeon $otherDungeon */
            $otherDungeon = Dungeon::query()->where('id', '!=', $route->dungeon_id)->firstOrFail();
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery(['dungeon_ids' => [$otherDungeon->id, $route->dungeon_id]]));

            // Assert
            $response->assertOk();
            $this->assertSame([$route->public_key], array_column($response->json('data'), 'public_key'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenDungeonIdsWithoutTheRoutesDungeon_returnsNoRoutes(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user  = $this->createUserWithUserRole();
            $route = $this->createOwnRoute($user);
            /** @var Dungeon $otherDungeon */
            $otherDungeon = Dungeon::query()->where('id', '!=', $route->dungeon_id)->firstOrFail();
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery(['dungeon_ids' => [$otherDungeon->id]]));

            // Assert
            $response->assertOk();
            $this->assertSame([], $response->json('data'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenAllScopeConstraintsMatchingAnUnpublishedOwnRoute_returnsItWithTheFieldsAPickerRowShows(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user = $this->createUserWithUserRole();
            /** @var Season $season */
            $season = Season::query()->firstOrFail();
            $route  = $this->createOwnRoute($user, [
                'season_id'          => $season->id,
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
                'level_min'          => 4,
                'level_max'          => 12,
            ]);
            $this->actingAs($user);

            // Act
            $response = $this->get($this->mineQuery([
                'game_version_id' => $route->mappingVersion->game_version_id,
                'season_id'       => $season->id,
                'dungeon_ids'     => [$route->dungeon_id],
            ]));

            // Assert
            $response->assertOk();
            $response->assertJsonCount(1, 'data');
            $response->assertJsonPath('data.0.public_key', $route->public_key);
            $response->assertJsonPath('data.0.published', PublishedState::UNPUBLISHED);
            $response->assertJsonPath('data.0.level_min', 4);
            $response->assertJsonPath('data.0.level_max', 12);
            $response->assertJsonPath('data.0.dungeon.key', $route->dungeon->key);
            $this->assertArrayHasKey('shortname', $response->json('data.0.dungeon.expansion'));
            $this->assertArrayHasKey('enemy_forces_required', $response->json('data.0'));
            $this->assertArrayHasKey('has_thumbnail', $response->json('data.0'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenScopeConstraintsWithoutMine_stillOnlyReturnsWorldPublishedRoutes(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user  = $this->createUserWithUserRole();
            $route = $this->createOwnRoute($user, [
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            ]);
            $this->actingAs($user);

            // Act
            $response = $this->get(sprintf(
                '/ajax/routes?%s',
                http_build_query(array_merge($this->dataTablesParameters($route->title), [
                    'game_version_id' => $route->mappingVersion->game_version_id,
                    'dungeon_ids'     => [$route->dungeon_id],
                ])),
            ));

            // Assert
            $response->assertOk();
            $this->assertNotContains($route->public_key, array_column($response->json('data'), 'public_key'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('invalidScopeParametersProvider')]
    public function get_givenInvalidScopeParameter_returnsUnprocessableEntity(array $parameters, string $errorKey): void
    {
        // Arrange
        $query = http_build_query(array_merge($this->dataTablesParameters(''), $parameters));

        // Act
        // The route tables request JSON, so a validation failure answers with a 422 instead of a redirect
        $response = $this->getJson(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($errorKey);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidScopeParametersProvider(): array
    {
        return [
            'unknown game version'     => [['game_version_id' => 999999], 'game_version_id'],
            'non-integer game version' => [['game_version_id' => 'retail'], 'game_version_id'],
            'unknown season'           => [['season_id' => 999999], 'season_id'],
            'non-integer season'       => [['season_id' => 'current'], 'season_id'],
            'dungeon ids not a list'   => [['dungeon_ids' => '12'], 'dungeon_ids'],
            'unknown dungeon'          => [['dungeon_ids' => [999999]], 'dungeon_ids.0'],
            'non-integer dungeon'      => [['dungeon_ids' => ['abc']], 'dungeon_ids.0'],
            'duplicate dungeon'        => [['dungeon_ids' => [1, 1]], 'dungeon_ids.0'],
        ];
    }

    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createOwnRoute(User $user, array $attributes = []): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            // A sandbox route never shows up in a route table
            'expires_at' => null,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $scopeParameters
     */
    private function mineQuery(array $scopeParameters): string
    {
        return sprintf(
            '/ajax/routes?%s',
            http_build_query(array_merge($this->dataTablesParameters(''), ['mine' => 1], $scopeParameters)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function dataTablesParameters(string $title): array
    {
        return [
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 'title',
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => $title, 'regex' => 'false'],
                ],
            ],
            'order'  => [['column' => 0, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ];
    }
}
