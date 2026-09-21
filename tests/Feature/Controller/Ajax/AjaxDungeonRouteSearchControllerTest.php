<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Service\DungeonRoute\DungeonRouteKillZoneServiceInterface;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The map's dungeon route search sidebar, which lists its results as route picker rows.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteSearchControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function get_givenMatchingRoutes_returnsThemAsRoutePickerRows(): void
    {
        // Arrange
        $user       = null;
        $route      = null;
        $otherRoute = null;

        try {
            $title = Str::random(30);
            $user  = User::factory()->create();
            $route = $this->createPublishedRoute($user, [
                'title'        => sprintf('%s first', $title),
                'popularity'   => 2,
                'level_min'    => 4,
                'level_max'    => 12,
                'views'        => 1234,
                'rating'       => 7,
                'rating_count' => 3,
                'enemy_forces' => 250,
            ]);
            $otherRoute = $this->createPublishedRoute($user, [
                'title'              => sprintf('%s second', $title),
                'popularity'         => 1,
                'dungeon_id'         => $route->dungeon_id,
                'mapping_version_id' => $route->mapping_version_id,
            ]);
            $enemy = Enemy::query()
                ->where('mapping_version_id', $route->mapping_version_id)
                ->whereNotNull('npc_id')
                ->whereNotNull('floor_id')
                ->firstOrFail();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create(['dungeon_route_id' => $route->id, 'floor_id' => $enemy->floor_id, 'index' => 1]);

            // Act
            $response = $this->post($this->searchUrl($route->mappingVersion), ['title' => $title]);

            // Assert
            $response->assertOk();
            $response->assertJsonCount(2);
            $this->assertSame([$route->public_key, $otherRoute->public_key], array_column($response->json(), 'public_key'));
            $response->assertJson([[
                'public_key'                    => $route->public_key,
                'title'                         => $route->title,
                'published'                     => PublishedState::WORLD,
                'level_min'                     => 4,
                'level_max'                     => 12,
                'views'                         => 1234,
                'rating'                        => 7,
                'rating_count'                  => 3,
                'enemy_forces'                  => 250,
                'enemy_forces_required'         => $route->mappingVersion->enemy_forces_required,
                'enemy_forces_required_teeming' => $route->mappingVersion->enemy_forces_required_teeming,
                'has_thumbnail'                 => false,
                'thumbnails'                    => [],
                'dungeon'                       => [
                    'id'        => $route->dungeon->id,
                    'name'      => $route->dungeon->name,
                    'key'       => $route->dungeon->key,
                    'expansion' => ['shortname' => $route->dungeon->expansion->shortname],
                ],
            ]]);
            $expectedPullForces = app(DungeonRouteKillZoneServiceInterface::class)
                ->getEnemyForcesPerKillZone($route)
                ->map(static fn(KillZoneEnemyForces $pull): array => [
                    'enemy_forces' => $pull->enemyForces,
                    'has_boss'     => $pull->hasBoss,
                ])
                ->values()
                ->all();
            $this->assertCount(1, $expectedPullForces);
            $this->assertSame($expectedPullForces, $response->json('0.pull_forces'));
            $this->assertSame([], $response->json('1.pull_forces'));
        } finally {
            $otherRoute?->delete();
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenNoMatchingRoute_returnsNoContent(): void
    {
        // Arrange
        [, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        // Act
        $response = $this->post($this->searchUrl($mappingVersion), ['title' => Str::random(40)]);

        // Assert
        $response->assertNoContent();
    }

    #[Test]
    public function get_givenAnUnpublishedRoute_leavesItOut(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user  = User::factory()->create();
            $route = $this->createPublishedRoute($user, [
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            ]);

            // Act
            $response = $this->post($this->searchUrl($route->mappingVersion), ['title' => $route->title]);

            // Assert
            $response->assertNoContent();
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }

    #[Test]
    public function get_givenAnInvalidLimit_returnsUnprocessableEntity(): void
    {
        // Arrange
        [, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        // Act
        $response = $this->post($this->searchUrl($mappingVersion), ['limit' => 11]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonStructure(['data' => ['limit']]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createPublishedRoute(User $user, array $attributes = []): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'title'              => Str::random(40),
            'teeming'            => 0,
            'clone_of'           => null,
            'expires_at'         => null,
        ], $attributes));
    }

    private function searchUrl(MappingVersion $mappingVersion): string
    {
        return sprintf(
            '/ajax/dungeonroute/search/%s/%s',
            $mappingVersion->gameVersion->key,
            $mappingVersion->dungeon->slug,
        );
    }
}
