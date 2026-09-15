<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\DungeonRoute\DungeonRouteRating;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
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
    public function get_givenDungeonColumnEntryWithNameOnly_returnsOk(): void
    {
        // Arrange - a columns[] entry that names the dungeon column but carries neither
        // 'searchable' nor 'orderable' (PHP-LARAVEL-S9, #4438)
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data' => 0,
                    'name' => 'dungeon_id',
                ],
            ],
            'order'  => [['column' => 0]],
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
            $response = $this->get($this->signedMdtExportUrl($dungeonRoute));

            // Assert
            $response->assertStatus(400);
            $logSpy->shouldNotHaveReceived('error');
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Guards #4538: the endpoint's only gates used to be client-settable headers (X-Requested-With
     * via OnlyAjax), which are forbidden for browser JavaScript but free for curl - so a list of
     * public keys could be walked straight to MDT strings, one cheap request each. The signature is
     * minted at page render instead, which cannot ride on the session because embeds get no cookies
     * at all (#4532).
     */
    #[Test]
    public function mdtExport_givenUnsignedUrl_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createMdtSupportedDungeonRoute();

        try {
            // Act
            $response = $this->get(sprintf('/ajax/%s/mdtExport?useCache=1', $dungeonRoute->public_key));

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function mdtExport_givenValidSignedUrl_returnsMdtString(): void
    {
        // Arrange
        $dungeonRoute = $this->createMdtSupportedDungeonRoute();

        try {
            // Act
            $response = $this->get($this->signedMdtExportUrl($dungeonRoute));

            // Assert
            $response->assertSuccessful();
            $response->assertJsonStructure(['mdt_string', 'warnings']);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function mdtExport_givenExpiredSignedUrl_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createMdtSupportedDungeonRoute();
        $url          = $this->signedMdtExportUrl($dungeonRoute);

        try {
            // Act - past the configured expiry window, whatever it is set to
            $this->travel(config('keystoneguru.mdt.export_url_expiry_hours') + 1)->hours();
            $response = $this->get($url);

            // Assert
            $response->assertForbidden();
        } finally {
            $this->travelBack();
            $dungeonRoute->delete();
        }
    }

    /**
     * The signature covers the path, so a url minted for one route cannot be replayed against
     * another - which is what keeps a single harvested url from becoming a key to the whole site.
     */
    #[Test]
    public function mdtExport_givenSignedUrlOfAnotherRoute_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute      = $this->createMdtSupportedDungeonRoute();
        $otherDungeonRoute = $this->createMdtSupportedDungeonRoute();

        try {
            // Act
            $url      = str_replace($dungeonRoute->public_key, $otherDungeonRoute->public_key, $this->signedMdtExportUrl($dungeonRoute));
            $response = $this->get($url);

            // Assert
            $response->assertForbidden();
        } finally {
            $otherDungeonRoute->delete();
            $dungeonRoute->delete();
        }
    }

    /**
     * useCache=0 skips the export cache and regenerates the string, so it is the expensive variant.
     * It is only handed out for the map editor, and the signature is what stops a viewer from
     * flipping the cheap url into it.
     */
    #[Test]
    public function mdtExport_givenSignedUrlWithFlippedUseCache_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createMdtSupportedDungeonRoute();

        try {
            // Act
            $url      = str_replace('useCache=1', 'useCache=0', $this->signedMdtExportUrl($dungeonRoute, useCache: true));
            $response = $this->get($url);

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Mints the signed url exactly as MapContextDungeonRoute does at page render.
     */
    private function signedMdtExportUrl(DungeonRoute $dungeonRoute, bool $useCache = true): string
    {
        return URL::temporarySignedRoute(
            'api.dungeonroute.mdtexport',
            now()->addHours(config('keystoneguru.mdt.export_url_expiry_hours')),
            [
                'dungeonRoute' => $dungeonRoute,
                'useCache'     => $useCache ? 1 : 0,
            ],
            absolute: false,
        );
    }

    private function createMdtSupportedDungeonRoute(): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            resolve: static fn(Dungeon $dungeon) => $dungeon->mdt_supported ? true : null,
        );

        return DungeonRoute::factory()->create([
            'expires_at'         => null,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
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

    #[Test]
    public function rate_givenRouteUserMayNotView_returnsForbidden(): void
    {
        // Arrange
        $rater        = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::UNPUBLISHED);

        try {
            $this->actingAs($rater);

            // Act
            $response = $this->post(sprintf('/ajax/%s/rate', $dungeonRoute->public_key), ['rating' => 8]);

            // Assert
            $response->assertForbidden();
            $this->assertSame(0, DungeonRouteRating::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            DungeonRouteRating::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $rater->delete();
        }
    }

    #[Test]
    public function rate_givenRouteUserMayView_returnsNewRating(): void
    {
        // Arrange
        $rater        = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);

        try {
            $this->actingAs($rater);

            // Act
            $response = $this->post(sprintf('/ajax/%s/rate', $dungeonRoute->public_key), ['rating' => 8]);

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['new_rating']);
            $this->assertEquals(
                8,
                DungeonRouteRating::query()
                    ->where('dungeon_route_id', $dungeonRoute->id)
                    ->where('user_id', $rater->id)
                    ->value('rating'),
            );
        } finally {
            DungeonRouteRating::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $rater->delete();
        }
    }

    #[Test]
    public function rateDelete_givenRouteUserMayNotView_returnsForbidden(): void
    {
        // Arrange
        $rater        = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::UNPUBLISHED);
        $rating       = DungeonRouteRating::forceCreate([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $rater->id,
            'rating'           => 8,
        ]);

        try {
            $this->actingAs($rater);

            // Act
            $response = $this->delete(sprintf('/ajax/%s/rate', $dungeonRoute->public_key));

            // Assert
            $response->assertForbidden();
            $this->assertNotNull($rating->fresh());
        } finally {
            DungeonRouteRating::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $rater->delete();
        }
    }

    #[Test]
    public function rateDelete_givenRouteUserMayView_removesTheRating(): void
    {
        // Arrange
        $rater        = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);
        $rating       = DungeonRouteRating::forceCreate([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $rater->id,
            'rating'           => 8,
        ]);

        try {
            $this->actingAs($rater);

            // Act
            $response = $this->delete(sprintf('/ajax/%s/rate', $dungeonRoute->public_key));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['new_rating']);
            $this->assertNull($rating->fresh());
        } finally {
            DungeonRouteRating::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $rater->delete();
        }
    }

    #[Test]
    public function favorite_givenRouteUserMayNotView_returnsForbidden(): void
    {
        // Arrange
        $user         = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::UNPUBLISHED);

        try {
            $this->actingAs($user);

            // Act
            $response = $this->post(sprintf('/ajax/%s/favorite', $dungeonRoute->public_key));

            // Assert
            $response->assertForbidden();
            $this->assertSame(0, DungeonRouteFavorite::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            DungeonRouteFavorite::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function favorite_givenRouteUserMayView_createsTheFavorite(): void
    {
        // Arrange
        $user         = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::WORLD);

        try {
            $this->actingAs($user);

            // Act
            $response = $this->post(sprintf('/ajax/%s/favorite', $dungeonRoute->public_key));

            // Assert
            $response->assertNoContent();
            $this->assertSame(1, DungeonRouteFavorite::query()
                ->where('dungeon_route_id', $dungeonRoute->id)
                ->where('user_id', $user->id)
                ->count());
        } finally {
            DungeonRouteFavorite::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function favoriteDelete_givenRouteUserMayNoLongerView_removesTheFavorite(): void
    {
        // Arrange - a route that was favorited while it was published and has since been unpublished
        $user         = $this->createUserWithUserRole();
        $dungeonRoute = $this->createRouteOwnedByAnotherUser(PublishedState::UNPUBLISHED);
        DungeonRouteFavorite::create(['dungeon_route_id' => $dungeonRoute->id, 'user_id' => $user->id]);

        try {
            $this->actingAs($user);

            // Act
            $response = $this->delete(sprintf('/ajax/%s/favorite', $dungeonRoute->public_key));

            // Assert
            $response->assertNoContent();
            $this->assertSame(0, DungeonRouteFavorite::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            DungeonRouteFavorite::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function get_givenMineAndFavorites_returnsOwnUnpublishedRouteButNotAnotherUsersFavoritedOne(): void
    {
        // Arrange
        $user        = $this->createUserWithUserRole();
        $othersRoute = $this->createRouteOwnedByAnotherUser(PublishedState::UNPUBLISHED);
        $ownRoute    = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'expires_at'         => null,
            'title'              => $othersRoute->title,
        ]);

        DungeonRouteFavorite::create(['dungeon_route_id' => $othersRoute->id, 'user_id' => $user->id]);
        DungeonRouteFavorite::create(['dungeon_route_id' => $ownRoute->id, 'user_id' => $user->id]);

        try {
            $this->actingAs($user);

            // Act - both routes carry the same title, so the search matches both and only the
            // visibility rules can account for one of them being absent
            $response = $this->get(sprintf(
                '/ajax/routes?%s&mine=1&favorites=1',
                $this->titleSearchQuery($othersRoute->title),
            ));

            // Assert
            $response->assertOk();
            $publicKeys = array_column($response->json('data'), 'public_key');
            $this->assertContains($ownRoute->public_key, $publicKeys);
            $this->assertNotContains($othersRoute->public_key, $publicKeys);
        } finally {
            DungeonRouteFavorite::query()->whereIn('dungeon_route_id', [$othersRoute->id, $ownRoute->id])->delete();
            $ownRoute->delete();
            $othersRoute->delete();
            $user->delete();
        }
    }

    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /**
     * A non-sandbox route authored by user 1. Sandbox routes (expires_at set, which the factory does
     * by default) are viewable and editable by anyone by design, so expires_at must be null for a
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
