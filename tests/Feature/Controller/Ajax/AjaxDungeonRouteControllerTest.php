<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function get_givenMissingColumnsParameter_returnsUnprocessableEntity(): void
    {
        // Arrange - no columns parameter in the request

        // Act
        $response = $this->get('/ajax/routes');

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function get_givenTagsParameterIsTheStringUndefined_returnsOk(): void
    {
        // Arrange - this mirrors the request sent by a DungeonrouteTable instance whose tags
        // select isn't rendered for its view (e.g. the team edit page's Route Publishing tab),
        // where jQuery's `.val()` on the missing element resolves to `undefined`
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
            'tags'   => 'undefined',
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenRequirementsParameterIsTheStringUndefined_returnsOk(): void
    {
        // Arrange - same failure mode as the tags parameter above: a requirements select that
        // isn't rendered for the current view sends the literal string 'undefined'
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'search'       => ['value' => '', 'regex' => 'false'],
            'requirements' => 'undefined',
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenDungeonHasNewerMappingVersionThanTheRoute_returnsDungeonLatestMappingVersionIdOfTheDungeonsNewestVersion(): void
    {
        // Arrange - expires_at must be null/0, otherwise the route is treated as a temporary
        // "try" route and excluded from the results entirely
        $dungeonRoute          = DungeonRoute::factory()->create(['expires_at' => null]);
        $currentMappingVersion = $dungeonRoute->dungeon->getCurrentMappingVersion();

        $newerMappingVersion = MappingVersion::create([
            'game_version_id'                 => $currentMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeonRoute->dungeon_id,
            'version'                         => $currentMappingVersion->version + 1,
            'enemy_forces_required'           => $currentMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $currentMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $currentMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $currentMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $currentMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        try {
            // Act
            $response = $this->get(sprintf('/ajax/routes?%s', $this->titleSearchQuery($dungeonRoute->title)));

            // Assert - compare against the dungeon's actual latest mapping version id (rather than
            // assuming it's still $newerMappingVersion) since this is a shared, persistent test DB
            // where other tests may concurrently add mapping versions for the same randomly-picked dungeon
            $response->assertOk();
            $data                         = $this->findRouteInResponseData($response->json('data'), $dungeonRoute->public_key);
            $actualLatestMappingVersionId = MappingVersion::where('dungeon_id', $dungeonRoute->dungeon_id)->max('id');
            $this->assertSame($actualLatestMappingVersionId, $data['dungeon_latest_mapping_version_id']);
            $this->assertGreaterThanOrEqual($newerMappingVersion->id, $data['dungeon_latest_mapping_version_id']);
            $this->assertNotSame($data['mapping_version_id'], $data['dungeon_latest_mapping_version_id']);
        } finally {
            $newerMappingVersion->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function get_givenDungeonHasNewerMappingVersionForADifferentGameVersion_returnsDungeonLatestMappingVersionIdScopedToTheRoutesOwnGameVersion(): void
    {
        // Arrange - expires_at must be null/0, otherwise the route is treated as a temporary
        // "try" route and excluded from the results entirely
        $dungeonRoute          = DungeonRoute::factory()->create(['expires_at' => null]);
        $currentMappingVersion = $dungeonRoute->dungeon->getCurrentMappingVersion();

        // Some dungeons have mapping versions for multiple game versions (e.g. retail and classic);
        // a higher id for a DIFFERENT game version must not be treated as "newer" for this route,
        // since mapping version id ranges aren't comparable across game versions
        /** @var GameVersion $otherGameVersion */
        $otherGameVersion = GameVersion::where('id', '!=', $currentMappingVersion->game_version_id)->firstOrFail();

        $otherGameVersionMappingVersion = MappingVersion::create([
            'game_version_id'                 => $otherGameVersion->id,
            'dungeon_id'                      => $dungeonRoute->dungeon_id,
            'version'                         => 1,
            'enemy_forces_required'           => $currentMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $currentMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $currentMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $currentMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $currentMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        try {
            // Act
            $response = $this->get(sprintf('/ajax/routes?%s', $this->titleSearchQuery($dungeonRoute->title)));

            // Assert - the other game version's mapping version has a higher id, but must not be
            // reported as the route's dungeon_latest_mapping_version_id
            $response->assertOk();
            $data                                       = $this->findRouteInResponseData($response->json('data'), $dungeonRoute->public_key);
            $actualLatestMappingVersionIdForGameVersion = MappingVersion::where('dungeon_id', $dungeonRoute->dungeon_id)
                ->where('game_version_id', $currentMappingVersion->game_version_id)
                ->max('id');
            $this->assertSame($actualLatestMappingVersionIdForGameVersion, $data['dungeon_latest_mapping_version_id']);
            $this->assertNotSame($otherGameVersionMappingVersion->id, $data['dungeon_latest_mapping_version_id']);
        } finally {
            $otherGameVersionMappingVersion->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function get_givenDungeonHasNoNewerMappingVersionThanTheRoute_returnsDungeonLatestMappingVersionIdMatchingTheDungeonsActualLatestVersion(): void
    {
        // Arrange - expires_at must be null/0, otherwise the route is treated as a temporary
        // "try" route and excluded from the results entirely
        $dungeonRoute = DungeonRoute::factory()->create(['expires_at' => null]);

        try {
            // Act
            $response = $this->get(sprintf('/ajax/routes?%s', $this->titleSearchQuery($dungeonRoute->title)));

            // Assert - compare against the dungeon's actual latest mapping version id (rather than
            // assuming $dungeonRoute->mapping_version_id is still it) since this is a shared,
            // persistent test DB where other tests may concurrently add mapping versions for the
            // same randomly-picked dungeon
            $response->assertOk();
            $data                         = $this->findRouteInResponseData($response->json('data'), $dungeonRoute->public_key);
            $actualLatestMappingVersionId = MappingVersion::where('dungeon_id', $dungeonRoute->dungeon_id)->max('id');
            $this->assertSame($actualLatestMappingVersionId, $data['dungeon_latest_mapping_version_id']);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>> $responseData
     * @return array<string, mixed>
     */
    private function findRouteInResponseData(array $responseData, string $dungeonRoutePublicKey): array
    {
        foreach ($responseData as $route) {
            if ($route['public_key'] === $dungeonRoutePublicKey) {
                return $route;
            }
        }

        $this->fail(sprintf('Route public_key=%s was not found in the response data', $dungeonRoutePublicKey));
    }

    private function titleSearchQuery(string $title): string
    {
        return http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => $title, 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);
    }
}
