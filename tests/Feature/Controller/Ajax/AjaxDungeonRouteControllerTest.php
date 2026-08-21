<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

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
    public function get_givenSearchParameterIsMissing_returnsOk(): void
    {
        // Arrange - a well-formed request that omits the top-level datatables 'search'
        // parameter entirely, rather than sending 'search[value]=""'
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
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenColumnEntryIsMissingName_returnsOk(): void
    {
        // Arrange - a malformed columns[] entry (missing the 'name' key), as seen in
        // PHP-LARAVEL-SA (#4084): a partial/mangled datatables columns payload from the client
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenSearchValueIsAnArray_returnsOk(): void
    {
        // Arrange - a caller sending 'search[value][]=...' instead of a scalar 'search[value]'
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
            'search' => ['value' => ['not', 'a', 'string'], 'regex' => 'false'],
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
    public function get_givenRouteAttributesColumnSearchValueIsTheStringUndefined_returnsOk(): void
    {
        // Arrange - same failure mode as the tags/requirements parameters above, but for the
        // routeattributes.name DataTables column's own search value: a route-attributes select
        // that isn't rendered for the current view sends the literal string 'undefined' instead
        // of an array of attribute ids, which DungeonRouteAttributesColumnHandler::applyFilter()
        // passed straight into array_diff()'s second (array) argument
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
                [
                    'data'       => 1,
                    'name'       => 'routeattributes.name',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => 'undefined', 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenAffixesColumnSearchValueIsTheStringUndefined_returnsOk(): void
    {
        // Arrange - same failure mode as the routeattributes.name column above, but for the
        // affixes.id column: an affixes select that isn't rendered for the current view sends the
        // literal string 'undefined' instead of an array of affix ids, which
        // DungeonRouteAffixesColumnHandler::applyFilter() passed straight into whereIn()
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
                [
                    'data'       => 1,
                    'name'       => 'affixes.id',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => 'undefined', 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenDungeonHasNewerMappingVersionThanTheRoute_returnsDungeonLatestMappingVersionIdOfTheDungeonsNewestVersion(): void
    {
        // Arrange
        $dungeonRoute          = $this->createDungeonRouteForActiveDungeon();
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
            // Act + Assert
            [$data, $actualLatestMappingVersionId] = $this->requestAndComputeActualLatestMappingVersionId(
                $dungeonRoute,
                $currentMappingVersion->game_version_id,
            );
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
        // Arrange
        $dungeonRoute          = $this->createDungeonRouteForActiveDungeon();
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
            // Act + Assert - the other game version's mapping version has a higher id, but must
            // not be reported as the route's dungeon_latest_mapping_version_id
            [$data, $actualLatestMappingVersionIdForGameVersion] = $this->requestAndComputeActualLatestMappingVersionId(
                $dungeonRoute,
                $currentMappingVersion->game_version_id,
            );
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
        // Arrange
        $dungeonRoute          = $this->createDungeonRouteForActiveDungeon();
        $currentMappingVersion = $dungeonRoute->dungeon->getCurrentMappingVersion();

        try {
            // Act + Assert
            [$data, $actualLatestMappingVersionId] = $this->requestAndComputeActualLatestMappingVersionId(
                $dungeonRoute,
                $currentMappingVersion->game_version_id,
            );
            $this->assertSame($actualLatestMappingVersionId, $data['dungeon_latest_mapping_version_id']);
            // Strict/type-safe comparison, matching how table.js decides whether to show the "new
            // mapping version available" warning icon (`row.dungeon_latest_mapping_version_id !== row.mapping_version_id`)
            $this->assertSame($data['mapping_version_id'], $data['dungeon_latest_mapping_version_id']);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * DungeonRoute::factory() doesn't filter out inactive dungeons; a route on an inactive dungeon
     * never appears in /ajax/routes results (in non-local envs), which would make these tests flake.
     */
    private function createDungeonRouteForActiveDungeon(): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        return DungeonRoute::factory()->create([
            // A "try" route is treated as temporary and excluded from the results entirely
            'expires_at'         => null,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }

    /**
     * Guards #3908: mdtExport() already returned a clean 400 for a dungeon MDTDungeon doesn't
     * recognize (mdt_supported === false) - InvalidMDTDungeonException never surfaced uncaught, via
     * the pre-existing generic catch (Exception). The regression this guards is that generic catch's
     * Log::error() call, which forwarded this expected, user-input-driven case to Sentry as a
     * false-positive bug report (the sentry log channel alerts on error-level logs; see
     * config/logging.php) - so the load-bearing assertion below is shouldNotHaveReceived('error'),
     * not the 400 (which passed even before this fix).
     */
    #[Test]
    public function get_givenDungeonNotSupportedByMdt_returnsBadRequestWithoutLoggingAnError(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            resolve: static fn(Dungeon $dungeon) => $dungeon->mdt_supported ? null : true,
        );
        $dungeonRoute = DungeonRoute::factory()->create([
            'expires_at'         => null,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // A spy (rather than shouldReceive(), which replaces Log entirely with a strict mock) only
        // observes calls without failing the test over any OTHER log call made along the way
        $logSpy = Log::spy();

        try {
            // Act
            $response = $this->get(sprintf('/ajax/%s/mdtExport', $dungeonRoute->public_key));

            // Assert
            $response->assertStatus(400);
            $logSpy->shouldNotHaveReceived('error');
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Guards #4083: unlike htmlsearch() (whose FormRequest rejects an unrecognised `?expansion=`
     * shortname via Rule::in() before it ever reaches the controller), htmlsearchcategory() takes
     * a plain Request and looks the shortname up directly - an unknown shortname makes
     * Expansion::where()->first() return null, which was then passed straight into
     * BaseDiscoverService::withExpansion()/ExpansionService::getCurrentAffixGroup() - both typed
     * to require a non-null Expansion - raising a TypeError instead of just skipping the filter.
     */
    #[Test]
    public function htmlsearchcategory_givenUnknownExpansionShortname_returnsSuccessfulInsteadOfTypeError(): void
    {
        // Act - no dungeon routes exist for the 'popular' category here, so a 204 (no content) is
        // the expected successful response; the regression this guards was a 500 TypeError
        $response = $this->get('/ajax/search/popular?expansion=not-a-real-expansion');

        // Assert
        $response->assertSuccessful();
    }

    /**
     * Requests the route and looks up the dungeon's actual latest mapping_versions.id for the
     * given game version. Most dungeons carry mapping versions for multiple game versions (e.g.
     * retail and classic), whose ids aren't comparable, so the lookup must be scoped the same way
     * the controller scopes it - otherwise this would compare against an unrelated game version.
     *
     * "Latest" is ranked by `version`, not `id`, matching the rest of the codebase's convention
     * (Dungeon::getCurrentMappingVersionForGameVersion(), MappingVersion::isLatestForDungeon()).
     *
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function requestAndComputeActualLatestMappingVersionId(DungeonRoute $dungeonRoute, int $gameVersionId): array
    {
        $response = $this->get(sprintf('/ajax/routes?%s', $this->titleSearchQuery($dungeonRoute->title)));
        $response->assertOk();
        $data = $this->findRouteInResponseData($response->json('data'), $dungeonRoute->public_key);

        /** @var MappingVersion $actualLatestMappingVersion */
        $actualLatestMappingVersion = MappingVersion::where('dungeon_id', $dungeonRoute->dungeon_id)
            ->where('game_version_id', $gameVersionId)
            ->orderByDesc('version')
            ->firstOrFail();

        return [$data, $actualLatestMappingVersion->id];
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
