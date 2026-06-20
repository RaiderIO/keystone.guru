<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Http\Requests\DungeonRoute\DungeonRouteSubmitTemporaryFormRequest;
use App\Models\CharacterClass;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\PublishedState;
use App\Models\Season;
use App\Service\DungeonRoute\DungeonRouteSaveService;
use App\Service\DungeonRoute\Logging\DungeonRouteSaveServiceLoggingInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteSaveService')]
final class DungeonRouteSaveServiceTest extends PublicTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * FormRequest has a real `method()` from Symfony Request that conflicts with PHPUnit's mock configurator,
     * so we use an anonymous class instead of a PHPUnit mock.
     *
     * @param array<string, mixed> $validated
     * @param array<string, mixed> $raw
     */
    private function mockFormRequest(array $validated, array $raw = []): FormRequest
    {
        return new class($validated, $raw) extends FormRequest {
            /**
             * @param array<string, mixed> $validatedData
             * @param array<string, mixed> $rawData
             */
            public function __construct(
                private readonly array $validatedData,
                private readonly array $rawData,
            ) {
            }

            public function validated($key = null, $default = null): mixed
            {
                return $this->validatedData;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->rawData[$key] ?? $default;
            }
        };
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function mockTemporaryRequest(array $validated): DungeonRouteSubmitTemporaryFormRequest
    {
        return new class($validated) extends DungeonRouteSubmitTemporaryFormRequest {
            /** @param array<string, mixed> $validatedData */
            public function __construct(private readonly array $validatedData)
            {
            }

            public function validated($key = null, $default = null): mixed
            {
                return $this->validatedData;
            }
        };
    }

    private function buildService(
        ?SeasonServiceInterface                  $seasonService = null,
        ?ThumbnailServiceInterface               $thumbnailService = null,
        ?DungeonRouteSaveServiceLoggingInterface $log = null,
    ): DungeonRouteSaveService {
        return new DungeonRouteSaveService(
            $seasonService ?? $this->createMockPublic(SeasonServiceInterface::class),
            $thumbnailService ?? $this->createMockPublic(ThumbnailServiceInterface::class),
            $log ?? $this->createMockPublic(DungeonRouteSaveServiceLoggingInterface::class),
        );
    }

    private function cleanupRoute(DungeonRoute $route): void
    {
        DungeonRouteAffixGroup::where('dungeon_route_id', $route->id)->delete();
        DungeonRoutePlayerClass::where('dungeon_route_id', $route->id)->delete();
        DungeonRoutePlayerSpecialization::where('dungeon_route_id', $route->id)->delete();
        DungeonRoutePlayerRace::where('dungeon_route_id', $route->id)->delete();
        DungeonRouteAttribute::where('dungeon_route_id', $route->id)->delete();
        $route->delete();
    }

    private function getRetailDungeon(): Dungeon
    {
        $count    = 0;
        $maxCount = 10;
        do {
            if ($count >= $maxCount) {
                $this->fail('Unable to find a retail dungeon with a current mapping version');
            }
            $dungeon = Dungeon::whereNotNull('challenge_mode_id')->inRandomOrder()->first();
            $count++;
        } while ($dungeon->getCurrentMappingVersion() === null);

        return $dungeon;
    }

    // -------------------------------------------------------------------------
    // saveFromRequest — new route
    // -------------------------------------------------------------------------

    #[Test]
    public function saveFromRequest_givenNewRoute_savesRouteAndReturnsTrue(): void
    {
        // Arrange
        $dungeon       = $this->getRetailDungeon();
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $service = $this->buildService(seasonService: $seasonService);
        $route   = new DungeonRoute();
        $request = $this->mockFormRequest([
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Test Route',
        ]);

        try {
            // Act
            $result = $service->saveFromRequest($route, $request);

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
    public function saveFromRequest_givenNewRoute_queuesThumbnailRefresh(): void
    {
        // Arrange
        $dungeon       = $this->getRetailDungeon();
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())->method('queueThumbnailRefresh')->willReturn(true);

        $service = $this->buildService(seasonService: $seasonService, thumbnailService: $thumbnailService);
        $route   = new DungeonRoute();
        $request = $this->mockFormRequest([
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Test Route',
        ]);

        try {
            // Act
            $service->saveFromRequest($route, $request);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
        // Assert: PHPUnit verifies expects($this->once()) automatically after the test
    }

    #[Test]
    public function saveFromRequest_givenNewRouteWithPlayerClasses_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon       = $this->getRetailDungeon();
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('queueThumbnailRefresh')->willReturn(true);

        $classId = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->value('id');
        $service = $this->buildService(seasonService: $seasonService, thumbnailService: $thumbnailService);
        $route   = new DungeonRoute();
        $request = $this->mockFormRequest([
            'dungeon_id'          => $dungeon->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Class Test',
            'class'               => [$classId],
        ]);

        try {
            // Act
            $result = $service->saveFromRequest($route, $request);

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
    public function saveFromRequest_givenNewRouteWithAffixGroups_createsJunctionRecords(): void
    {
        // Arrange
        $dungeon = $this->getRetailDungeon();
        $season  = Season::with('affixGroups.affixes')->find(Season::SEASON_TWW_S3);
        $this->assertNotNull($season, 'Season TWW S3 must exist in the database');

        // Pick a non-teeming affix group (teeming is false by default on new routes)
        $affixGroup = $season->affixGroups->first(fn($ag) => !$ag->hasAffix('Teeming') && $ag->id > 0);
        $this->assertNotNull($affixGroup, 'Expected at least one non-teeming affix group in TWW S3');

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('queueThumbnailRefresh')->willReturn(true);

        $service = $this->buildService(seasonService: $seasonService, thumbnailService: $thumbnailService);
        $route   = new DungeonRoute();
        $request = $this->mockFormRequest([
            'dungeon_id'           => $dungeon->id,
            'faction_id'           => 1,
            'dungeon_route_title'  => 'Affix Test',
            'route_select_affixes' => [(string)$affixGroup->id],
        ]);

        try {
            // Act
            $result = $service->saveFromRequest($route, $request);

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
    public function saveFromRequest_givenNewRouteWithTemplateFlag_clonesTemplateRelations(): void
    {
        // Arrange
        $demoRoute = DungeonRoute::factory()->create(['demo' => true, 'teeming' => false]);

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('queueThumbnailRefresh')->willReturn(true);

        $log = LoggingFixtures::createDungeonRouteSaveServiceLogging($this);
        $log->expects($this->once())->method('saveFromRequestTemplateCloneStart');
        $log->expects($this->once())->method('saveFromRequestTemplateCloneEnd');

        $service  = $this->buildService(seasonService: $seasonService, thumbnailService: $thumbnailService, log: $log);
        $newRoute = new DungeonRoute();
        $request  = $this->mockFormRequest([
            'dungeon_id'          => $demoRoute->dungeon_id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Template Test',
            'template'            => true,
        ]);

        try {
            // Act
            $result = $service->saveFromRequest($newRoute, $request);

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
    public function saveFromRequest_givenInvalidDungeonId_throwsModelNotFoundException(): void
    {
        // Arrange
        $service = $this->buildService();
        $route   = new DungeonRoute();
        $request = $this->mockFormRequest(['dungeon_id' => PHP_INT_MAX]);

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $service->saveFromRequest($route, $request);
    }

    // -------------------------------------------------------------------------
    // saveFromRequest — existing route (update)
    // -------------------------------------------------------------------------

    #[Test]
    public function saveFromRequest_givenExistingRoute_updatesRouteAndReturnsTrue(): void
    {
        // Arrange
        $route = DungeonRoute::factory()->create();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->never())->method('queueThumbnailRefresh');

        $service = $this->buildService(seasonService: $seasonService, thumbnailService: $thumbnailService);
        $request = $this->mockFormRequest([
            'dungeon_id'          => $route->dungeon_id,
            'faction_id'          => $route->faction_id,
            'dungeon_route_title' => 'Updated Title',
        ]);

        try {
            // Act
            $result = $service->saveFromRequest($route, $request);

            // Assert
            $this->assertTrue($result);
            $this->assertEquals('Updated Title', $route->fresh()->title);
        } finally {
            $this->cleanupRoute($route);
        }
    }

    #[Test]
    public function saveFromRequest_givenExistingRouteWithAffixGroups_replacesOldAffixGroups(): void
    {
        // Arrange
        $route  = DungeonRoute::factory()->create();
        $season = Season::with('affixGroups.affixes')->find(Season::SEASON_TWW_S3);
        $this->assertNotNull($season, 'Season TWW S3 must exist in the database');

        $affixGroups = $season->affixGroups->filter(fn($ag) => !$ag->hasAffix('Teeming') && $ag->id > 0)->values();
        $this->assertGreaterThanOrEqual(2, $affixGroups->count(), 'Need at least 2 non-teeming affix groups for this test');

        $oldAffixGroup = $affixGroups->get(0);
        $newAffixGroup = $affixGroups->get(1);

        DungeonRouteAffixGroup::create([
            'dungeon_route_id' => $route->id,
            'affix_group_id'   => $oldAffixGroup->id,
        ]);

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);

        $service = $this->buildService(seasonService: $seasonService);
        $request = $this->mockFormRequest([
            'dungeon_id'           => $route->dungeon_id,
            'faction_id'           => $route->faction_id,
            'route_select_affixes' => [(string)$newAffixGroup->id],
        ]);

        try {
            // Act
            $service->saveFromRequest($route, $request);

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

    // -------------------------------------------------------------------------
    // saveTemporaryFromRequest
    // -------------------------------------------------------------------------

    #[Test]
    public function saveTemporaryFromRequest_givenValidDungeonId_returnsTrueAndSetsExpiresAt(): void
    {
        // Arrange
        $dungeon       = $this->getRetailDungeon();
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $service = $this->buildService(seasonService: $seasonService);
        $route   = new DungeonRoute();
        $request = $this->mockTemporaryRequest(['dungeon_id' => $dungeon->id]);

        try {
            // Act
            $result = $service->saveTemporaryFromRequest($route, $request);

            // Assert
            $this->assertTrue($result);
            $this->assertNotNull($route->expires_at);
            $this->assertTrue(
                $route->expires_at->isFuture(),
                sprintf('Expected expires_at to be in the future, got: %s', $route->expires_at),
            );
            $this->assertNotEmpty($route->public_key);
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    #[Test]
    public function saveTemporaryFromRequest_givenValidDungeonId_setsHardcodedFields(): void
    {
        // Arrange
        $dungeon       = $this->getRetailDungeon();
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $service = $this->buildService(seasonService: $seasonService);
        $route   = new DungeonRoute();
        $request = $this->mockTemporaryRequest(['dungeon_id' => $dungeon->id]);

        try {
            // Act
            $service->saveTemporaryFromRequest($route, $request);

            // Assert
            $this->assertEquals(1, $route->faction_id, 'Temporary routes must have faction_id = 1');
            $this->assertFalse((bool)$route->teeming, 'Temporary routes must have teeming = false');
            $this->assertEquals(0, $route->seasonal_index, 'Temporary routes must have seasonal_index = 0');
            $this->assertEmpty($route->pull_gradient, 'Temporary routes must have empty pull_gradient');
        } finally {
            if ($route->exists) {
                $this->cleanupRoute($route);
            }
        }
    }

    // -------------------------------------------------------------------------
    // cloneRoute
    // -------------------------------------------------------------------------

    #[Test]
    public function cloneRoute_givenSourceRoute_createsRouteWithCloneOfAndNullTeamId(): void
    {
        // Arrange — cloneRoute uses Auth::id() for author_id which is NOT NULL in the DB
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create(['team_id' => null]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source);

            // Assert
            $this->assertTrue($clone->exists);
            $this->assertEquals($source->public_key, $clone->clone_of);
            $this->assertNull($clone->team_id);
            $this->assertNotEquals($source->public_key, $clone->public_key);
            $this->assertEquals($source->dungeon_id, $clone->dungeon_id);
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenUnpublishedTrue_setsUnpublishedPublishedState(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source, true);

            // Assert
            $this->assertEquals(
                PublishedState::ALL[PublishedState::UNPUBLISHED],
                $clone->published_state_id,
                'Cloning with unpublished=true must set published_state_id to UNPUBLISHED',
            );
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenUnpublishedFalse_copiesSourcePublishedState(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source, false);

            // Assert
            $this->assertEquals(
                PublishedState::ALL[PublishedState::WORLD],
                $clone->published_state_id,
                sprintf(
                    'Cloning with unpublished=false must copy published_state_id from source (%d)',
                    PublishedState::ALL[PublishedState::WORLD],
                ),
            );
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }
}
