<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\RouteAttribute;
use App\Models\Season;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\LoggingFixtures;

#[Group('DungeonRouteSaveService')]
final class DungeonRouteSaveServiceSaveTest extends DungeonRouteSaveServiceTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * A season service whose edit-path lookups (`getUpcomingSeasonForDungeon` /
     * `getMostRecentSeasonForDungeon`) both resolve to no season.
     *
     * @return MockObject&SeasonServiceInterface
     */
    private function noSeasonService(): MockObject
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        return $seasonService;
    }

    /**
     * A season service whose edit-path lookup resolves to the given season.
     *
     * @return MockObject&SeasonServiceInterface
     */
    private function seasonServiceReturning(Season $season): MockObject
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);

        return $seasonService;
    }

    private function loadTwwS3Season(): Season
    {
        $season = Season::with('affixGroups.affixes')->find(Season::SEASON_TWW_S3);
        $this->assertNotNull($season, 'Season TWW S3 must exist in the database');

        return $season;
    }

    // -------------------------------------------------------------------------
    // save — new route, happy paths
    // -------------------------------------------------------------------------

    #[Test]
    public function save_givenNewRoute_savesRouteAndReturnsTrue(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Test Route',
        ];

        try {
            // Act
            $result = $service->save($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertTrue($route->exists);
            $this->assertNotEmpty($route->public_key);
            $this->assertEquals($dungeon->id, $route->dungeon_id);
            $this->assertGreaterThan(0, $route->mapping_version_id);
            $this->assertEquals(1, $route->faction_id);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenNewRoute_queuesThumbnailRefresh(): void
    {
        // Arrange
        $dungeon = $this->getRetailDungeon();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())->method('queueThumbnailRefresh')->willReturn(true);

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $thumbnailService);
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Test Route',
        ];

        try {
            // Act
            $service->save($route, $validated);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
        // Assert: PHPUnit verifies expects($this->once()) automatically after the test
    }

    // -------------------------------------------------------------------------
    // save — input normalization
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('titleProvider')]
    public function save_givenTitle_resolvesExpectedTitle(string $inputTitle, bool $expectFallback): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => $inputTitle,
        ];
        $expected = $expectFallback ? __($dungeon->name) : $inputTitle;

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertSame($expected, $route->title);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function titleProvider(): array
    {
        return [
            'a real title is kept as-is'                        => ['My Cool Route', false],
            'an empty title falls back to the dungeon name'     => ['', true],
            'a slug-empty title falls back to the dungeon name' => ['!!! ###', true],
        ];
    }

    #[Test]
    #[DataProvider('factionIdProvider')]
    public function save_givenFactionId_resolvesExpectedFactionId(int $inputFactionId, int $expectedFactionId): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => $inputFactionId,
            'dungeon_route_title' => 'Faction Test',
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($expectedFactionId, $route->faction_id);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function factionIdProvider(): array
    {
        return [
            'zero falls back to Unspecified (1)' => [0, 1],
            'Horde is kept'                      => [2, 2],
            'Alliance is kept'                   => [3, 3],
        ];
    }

    #[Test]
    #[DataProvider('teamIdProvider')]
    public function save_givenTeamId_resolvesExpectedTeamId(int $inputTeamId, ?int $expectedTeamId): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Team Test',
            'team_id'             => $inputTeamId,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($expectedTeamId, $route->team_id);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    /**
     * @return array<string, array{0: int, 1: int|null}>
     */
    public static function teamIdProvider(): array
    {
        return [
            'zero becomes null'     => [0, null],
            'a positive id is kept' => [4242, 4242],
        ];
    }

    /**
     * @param array<int, int> $inputSeasonalIndex
     */
    #[Test]
    #[DataProvider('seasonalIndexProvider')]
    public function save_givenSeasonalIndex_storesParsedIndex(array $inputSeasonalIndex, int $expectedIndex): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Seasonal Index Test',
            'seasonal_index'      => $inputSeasonalIndex,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($expectedIndex, $route->seasonal_index);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    /**
     * @return array<string, array{0: array<int, int>, 1: int}>
     */
    public static function seasonalIndexProvider(): array
    {
        return [
            'first index zero' => [[0], 0],
            'a non-zero index' => [[3], 3],
        ];
    }

    #[Test]
    #[DataProvider('levelRangeProvider')]
    public function save_givenLevelRange_storesParsedLevels(string $rawLevel, int $expectedMin, ?int $expectedMax): void
    {
        // Arrange — no active season, so the season key level max used as the implicit upper bound is null
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Level Range Test',
            'dungeon_route_level' => $rawLevel,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($expectedMin, $route->level_min);
            $this->assertEquals($expectedMax, $route->level_max);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: int|null}>
     */
    public static function levelRangeProvider(): array
    {
        return [
            'explicit min and max'          => ['5;10', 5, 10],
            'min only leaves max unbounded' => ['8', 8, null],
        ];
    }

    #[Test]
    public function save_givenSpeedrunDungeonWithEnabledDifficulty_keepsChosenDifficulty(): void
    {
        // Arrange — pick a speedrun dungeon that has at least one enabled speedrun difficulty
        $dungeon = $this->getDungeonWithNonFacadeFloor(
            fn(Builder $query) => $query->where('speedrun_enabled', true)->whereHas('dungeonSpeedrunDifficulties'),
        );
        $enabledDifficulties = $dungeon->getEnabledSpeedrunDifficulties();
        $chosenDifficulty    = $enabledDifficulties[0];

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Speedrun Test',
            // A difficulty that is enabled for this dungeon should be respected
            'dungeon_difficulty' => $chosenDifficulty,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($chosenDifficulty, $route->dungeon_difficulty);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenSpeedrunDungeonWithDisabledDifficulty_fallsBackToFirstEnabled(): void
    {
        // Arrange — pick a speedrun dungeon that has at least one enabled speedrun difficulty
        $dungeon = $this->getDungeonWithNonFacadeFloor(
            fn(Builder $query) => $query->where('speedrun_enabled', true)->whereHas('dungeonSpeedrunDifficulties'),
        );
        $enabledDifficulties = $dungeon->getEnabledSpeedrunDifficulties();
        // A difficulty that is NOT enabled for this dungeon
        $disabledDifficulty = collect(Dungeon::DIFFICULTY_ALL)
            ->values()
            ->first(fn(int $difficulty) => !in_array($difficulty, $enabledDifficulties, true));

        $this->assertNotNull($disabledDifficulty, 'Expected a dungeon that does not enable every difficulty');

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Speedrun Fallback Test',
            'dungeon_difficulty'  => $disabledDifficulty,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert — falls back to the first enabled difficulty
            $this->assertEquals($enabledDifficulties[0], $route->dungeon_difficulty);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenNonSpeedrunDungeonWithDifficulty_keepsDifficulty(): void
    {
        // Arrange — retail M+ dungeons are not speedrun-enabled
        $dungeon = $this->getRetailDungeon();
        $this->assertFalse((bool)$dungeon->speedrun_enabled, 'Expected a non-speedrun retail dungeon');

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Difficulty Passthrough Test',
            'dungeon_difficulty'  => Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN], $route->dungeon_difficulty);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenAdminWithDemoFlag_setsDemoTrue(): void
    {
        // Arrange — user 1 is the seeded admin; the demo flag is only honored for admins
        Auth::loginUsingId(1);

        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Demo Test',
            'demo'                => 1,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertTrue((bool)$route->demo);
        } finally {
            Auth::logout();
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    // -------------------------------------------------------------------------
    // save — child relations
    // -------------------------------------------------------------------------

    #[Test]
    public function save_givenNewRouteWithPlayerClasses_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $classId   = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->value('id');
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Class Test',
            'class'               => [$classId],
        ];

        try {
            // Act
            $result = $service->save($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertEquals(
                1,
                DungeonRoutePlayerClass::where('dungeon_route_id', $route->id)->count(),
                sprintf('Expected 1 player class junction record for route %d', $route->id),
            );
            $this->assertEquals(
                $classId,
                DungeonRoutePlayerClass::where('dungeon_route_id', $route->id)->value('character_class_id'),
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenNewRouteWithPlayerRaces_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $raceId    = CharacterRace::query()->value('id');
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Race Test',
            'race'                => [$raceId],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(1, DungeonRoutePlayerRace::where('dungeon_route_id', $route->id)->count());
            $this->assertEquals(
                $raceId,
                DungeonRoutePlayerRace::where('dungeon_route_id', $route->id)->value('character_race_id'),
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenNewRouteWithSpecializations_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $specId    = CharacterClassSpecialization::query()->value('id');
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Spec Test',
            'specialization'      => [$specId],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(1, DungeonRoutePlayerSpecialization::where('dungeon_route_id', $route->id)->count());
            $this->assertEquals(
                $specId,
                DungeonRoutePlayerSpecialization::where('dungeon_route_id', $route->id)->value('character_class_specialization_id'),
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenNewRouteWithAttributes_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon     = $this->getRetailDungeon();
        $routeAttrId = RouteAttribute::query()->value('id');
        $service     = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route       = new DungeonRoute();
        $validated   = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Attribute Test',
            'attributes'          => [$routeAttrId],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(1, DungeonRouteAttribute::where('dungeon_route_id', $route->id)->count());
            $this->assertEquals(
                $routeAttrId,
                DungeonRouteAttribute::where('dungeon_route_id', $route->id)->value('route_attribute_id'),
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenExistingRouteWithPlayerClasses_replacesOldClasses(): void
    {
        // Arrange
        $route     = DungeonRoute::factory()->create();
        $warriorId = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->value('id');
        $mageId    = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->value('id');

        DungeonRoutePlayerClass::create(['dungeon_route_id' => $route->id, 'character_class_id' => $warriorId]);

        $service   = $this->buildService(seasonService: $this->noSeasonService());
        $validated = [
            'dungeon_id' => $route->dungeon_id,
            'faction_id' => $route->faction_id,
            'class'      => [$mageId],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(
                1,
                DungeonRoutePlayerClass::where('dungeon_route_id', $route->id)->count(),
                'Old player classes must be replaced, not appended',
            );
            $this->assertEquals(
                $mageId,
                DungeonRoutePlayerClass::where('dungeon_route_id', $route->id)->value('character_class_id'),
            );
        } finally {
            $this->cleanupRoute($route);
        }
    }

    // -------------------------------------------------------------------------
    // save — affix groups
    // -------------------------------------------------------------------------

    #[Test]
    public function save_givenNewRouteWithAffixGroups_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon = $this->getRetailDungeon();
        $season  = $this->loadTwwS3Season();

        // Pick a non-teeming affix group (teeming is false by default on new routes)
        $affixGroup = $season->affixGroups->first(fn($ag) => !$ag->hasAffix('Teeming') && $ag->id > 0);
        $this->assertNotNull($affixGroup, 'Expected at least one non-teeming affix group in TWW S3');

        $service   = $this->buildService(seasonService: $this->seasonServiceReturning($season), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'           => $dungeon->id,
            'faction_id'           => 1,
            'dungeon_route_title'  => 'Affix Test',
            'route_select_affixes' => [(string)$affixGroup->id],
        ];

        try {
            // Act
            $result = $service->save($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertEquals(
                1,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                sprintf('Expected 1 affix group junction record for route %d', $route->id),
            );
            $this->assertEquals(
                $affixGroup->id,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->value('affix_group_id'),
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenExistingRouteWithAffixGroups_replacesOldAffixGroups(): void
    {
        // Arrange
        $route  = DungeonRoute::factory()->create();
        $season = $this->loadTwwS3Season();

        $affixGroups = $season->affixGroups->filter(fn($ag) => !$ag->hasAffix('Teeming') && $ag->id > 0)->values();
        $this->assertGreaterThanOrEqual(2, $affixGroups->count(), 'Need at least 2 non-teeming affix groups for this test');

        $oldAffixGroup = $affixGroups->get(0);
        $newAffixGroup = $affixGroups->get(1);

        DungeonRouteAffixGroup::create([
            'dungeon_route_id' => $route->id,
            'affix_group_id'   => $oldAffixGroup->id,
        ]);

        $service   = $this->buildService(seasonService: $this->seasonServiceReturning($season));
        $validated = [
            'dungeon_id'           => $route->dungeon_id,
            'faction_id'           => $route->faction_id,
            'route_select_affixes' => [(string)$newAffixGroup->id],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(
                1,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                'Expected old affix group to be replaced by exactly one new affix group',
            );
            $this->assertEquals(
                $newAffixGroup->id,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->value('affix_group_id'),
                sprintf('Expected new affix group id %d', $newAffixGroup->id),
            );
        } finally {
            $this->cleanupRoute($route);
        }
    }

    #[Test]
    public function save_givenNewRouteWithoutAffixSelection_ensuresDefaultAffixGroup(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $season    = $this->loadTwwS3Season();
        $service   = $this->buildService(seasonService: $this->seasonServiceReturning($season), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Default Affix Test',
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertGreaterThanOrEqual(
                1,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                'A new route with an active season but no selection must get a default affix group',
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenInvalidAffixId_skipsAffix(): void
    {
        // Arrange
        $dungeon   = $this->getRetailDungeon();
        $season    = $this->loadTwwS3Season();
        $service   = $this->buildService(seasonService: $this->seasonServiceReturning($season), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'           => $dungeon->id,
            'faction_id'           => 1,
            'dungeon_route_title'  => 'Invalid Affix Test',
            'route_select_affixes' => [(string)PHP_INT_MAX],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(
                0,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                'An affix group not belonging to the active season must be skipped',
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenTeemingMismatchAffix_skipsAffix(): void
    {
        // Arrange — saved routes always have teeming = false, so a teeming affix group must be skipped
        $season = Season::with('affixGroups.affixes')
            ->whereHas('affixGroups.affixes', fn(Builder $query) => $query->where('key', 'teeming'))
            ->first();
        $this->assertNotNull($season, 'Expected a season containing a teeming affix group');

        $teemingGroup = $season->affixGroups->first(fn($ag) => $ag->id > 0 && $ag->hasAffix('Teeming'));
        $this->assertNotNull($teemingGroup, 'Expected a teeming affix group in the selected season');

        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->seasonServiceReturning($season), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'           => $dungeon->id,
            'faction_id'           => 1,
            'dungeon_route_title'  => 'Teeming Mismatch Test',
            'route_select_affixes' => [(string)$teemingGroup->id],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(
                0,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                'A teeming affix group must not be assigned to a non-teeming route',
            );
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenAffixSelectionButNoActiveSeason_clearsExistingAffixes(): void
    {
        // Arrange
        $route      = DungeonRoute::factory()->create();
        $season     = $this->loadTwwS3Season();
        $affixGroup = $season->affixGroups->first(fn($ag) => !$ag->hasAffix('Teeming') && $ag->id > 0);
        $this->assertNotNull($affixGroup, 'Expected at least one non-teeming affix group in TWW S3');

        DungeonRouteAffixGroup::create([
            'dungeon_route_id' => $route->id,
            'affix_group_id'   => $affixGroup->id,
        ]);

        // No active season for the edit path
        $service   = $this->buildService(seasonService: $this->noSeasonService());
        $validated = [
            'dungeon_id'           => $route->dungeon_id,
            'faction_id'           => $route->faction_id,
            'route_select_affixes' => [(string)$affixGroup->id],
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals(
                0,
                DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->count(),
                'Selecting affixes without an active season must clear existing affix groups',
            );
        } finally {
            $this->cleanupRoute($route);
        }
    }

    // -------------------------------------------------------------------------
    // save — template clone
    // -------------------------------------------------------------------------

    #[Test]
    public function save_givenNewRouteWithTemplateFlag_clonesTemplateRelations(): void
    {
        // Arrange
        $demoRoute = DungeonRoute::factory()->create(['demo' => true, 'teeming' => false]);

        $log = LoggingFixtures::createDungeonRouteSaveServiceLogging($this);
        $log->expects($this->once())->method('saveTemplateCloneStart');
        $log->expects($this->once())->method('saveTemplateCloneEnd');

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh(), log: $log);
        $newRoute  = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $demoRoute->dungeon_id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Template Test',
            'template'            => true,
        ];

        try {
            // Act
            $result = $service->save($newRoute, $validated);

            // Assert
            $this->assertTrue($result);
        } finally {
            if ($newRoute->exists) {
                $this->cleanupRoute($newRoute);
            }
            $this->cleanupRoute($demoRoute);
        }
    }

    #[Test]
    public function save_givenTemplateFlagButNoDemoRoute_doesNotClone(): void
    {
        // Arrange — pick a retail dungeon that has no demo route to use as a template
        $dungeon = $this->getDungeonWithNonFacadeFloor(
            fn(Builder $query) => $query
                ->whereNotNull('challenge_mode_id')
                ->whereDoesntHave('dungeonRoutes', fn(Builder $sub) => $sub->where('demo', true)),
        );

        $log = LoggingFixtures::createDungeonRouteSaveServiceLogging($this);
        $log->expects($this->never())->method('saveTemplateCloneStart');
        $log->expects($this->never())->method('saveTemplateCloneEnd');

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh(), log: $log);
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'No Template Test',
            'template'            => true,
        ];

        try {
            // Act
            $result = $service->save($route, $validated);

            // Assert
            $this->assertTrue($result);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    // -------------------------------------------------------------------------
    // save — existing route & failure paths
    // -------------------------------------------------------------------------

    #[Test]
    public function save_givenExistingRoute_updatesRouteAndReturnsTrue(): void
    {
        // Arrange
        $route = DungeonRoute::factory()->create();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->never())->method('queueThumbnailRefresh');

        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $thumbnailService);
        $validated = [
            'dungeon_id'          => $route->dungeon_id,
            'faction_id'          => $route->faction_id,
            'dungeon_route_title' => 'Updated Title',
        ];

        try {
            // Act
            $result = $service->save($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertEquals('Updated Title', $route->fresh()->title);
        } finally {
            $this->cleanupRoute($route);
        }
    }

    #[Test]
    public function save_givenExistingRouteWithoutDungeonId_usesExistingDungeonId(): void
    {
        // Arrange
        $route             = DungeonRoute::factory()->create();
        $originalDungeonId = $route->dungeon_id;

        $service   = $this->buildService(seasonService: $this->noSeasonService());
        $validated = [
            // intentionally no dungeon_id
            'faction_id'          => $route->faction_id,
            'dungeon_route_title' => 'No Dungeon Id Test',
        ];

        try {
            // Act
            $result = $service->save($route, $validated);

            // Assert
            $this->assertTrue($result);
            $this->assertEquals($originalDungeonId, $route->dungeon_id);
        } finally {
            $this->cleanupRoute($route);
        }
    }

    // -------------------------------------------------------------------------
    // save — dungeon start map icon
    // -------------------------------------------------------------------------

    #[Test]
    public function save_givenValidDungeonStartMapIcon_storesDungeonStartMapIconId(): void
    {
        // Arrange — a dungeon start icon belonging to the dungeon's current mapping version
        $dungeon   = $this->getRetailDungeon();
        $mapIcon   = $this->createDungeonStartMapIcon($dungeon->getCurrentMappingVersion()->id, $dungeon->floors->first()->id);
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'                => $dungeon->id,
            'faction_id'                => 1,
            'dungeon_route_title'       => 'Dungeon Start Test',
            'dungeon_start_map_icon_id' => $mapIcon->id,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($mapIcon->id, $route->dungeon_start_map_icon_id);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
            $mapIcon->delete();
        }
    }

    #[Test]
    public function save_givenForeignDungeonStartMapIcon_storesNull(): void
    {
        // Arrange — an id that does not exist as a dungeon start of the dungeon's mapping version
        $dungeon   = $this->getRetailDungeon();
        $service   = $this->buildService(seasonService: $this->noSeasonService(), thumbnailService: $this->thumbnailServiceAllowingRefresh());
        $route     = new DungeonRoute();
        $validated = [
            'dungeon_id'                => $dungeon->id,
            'faction_id'                => 1,
            'dungeon_route_title'       => 'Foreign Dungeon Start Test',
            'dungeon_start_map_icon_id' => PHP_INT_MAX,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertNull($route->dungeon_start_map_icon_id);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function save_givenExistingRouteOnNonCurrentMappingVersion_storesStartFromRoutesMappingVersion(): void
    {
        // Arrange — an existing route on a now-outdated mapping version. The chosen start belongs to
        // the route's own mapping version, so it must be validated against that (not the current one).
        $dungeon    = $this->getRetailDungeon();
        $existingMV = $dungeon->getCurrentMappingVersion();
        $newerMV    = $this->createNewerMappingVersion($dungeon, $existingMV);
        $route      = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
        $mapIcon    = $this->createDungeonStartMapIcon($existingMV->id, $dungeon->floors->first()->id);

        $service   = $this->buildService(seasonService: $this->noSeasonService());
        $validated = [
            'dungeon_id'                => $dungeon->id,
            'faction_id'                => $route->faction_id,
            'dungeon_start_map_icon_id' => $mapIcon->id,
        ];

        try {
            // Act
            $service->save($route, $validated);

            // Assert
            $this->assertEquals($mapIcon->id, $route->dungeon_start_map_icon_id);
        } finally {
            $this->cleanupRoute($route);
            $mapIcon->delete();
            $newerMV->delete();
        }
    }

    #[Test]
    public function save_givenInvalidDungeonId_throwsModelNotFoundException(): void
    {
        // Arrange
        $service   = $this->buildService();
        $route     = new DungeonRoute();
        $validated = ['dungeon_id' => PHP_INT_MAX];

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $service->save($route, $validated);
    }
}
