<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTMapPOIType;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * #3762 (second review round): Wotuu corrected the earlier decision to leave DungeonFloorSwitchMarker uncloned
 * on a genuinely first-ever import for a game version - modern MDT exports only generate a combined/facade
 * view and no longer supply the per-floor MapLink POI data MDTMappingImportService::importMapPOIs() used to
 * recreate floor switch markers from, yet the app still needs them for both facade mode and the per-floor
 * "visit style" mode. MappingService::createNewMappingVersionFromMDTMapping() now clones them from the
 * facade-source mapping version, just like FloorUnions/MountableAreas (see
 * MappingServiceCreateNewMappingVersionFromMDTMappingTest).
 *
 * That reintroduces a duplication risk: importMapPOIs() used to unconditionally (re)create a floor switch
 * marker from MDT's own MapLink POI data whenever $currentMappingVersion was null - exactly the condition
 * under which the clone above now happens. importMapPOIs() was changed to key off whether $newMappingVersion
 * already has floor switch markers of its own (from any source), instead of inspecting $currentMappingVersion.
 * This test verifies that directly - with a stubbed MDTDungeon, no real Lua - for both the "already cloned"
 * and "nothing to clone" cases.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingImportMapPOIsFloorSwitchMarkerTest extends PublicTestCase
{
    #[Test]
    public function importMapPOIs_givenNewMappingVersionAlreadyHasFloorSwitchMarkers_doesNotCreateDuplicateFromMDTMapLinkPOI(): void
    {
        // Arrange
        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        [$dungeon, $sourceMappingVersion, $sourceFloor, $targetFloor] = $this->getDungeonWithFloorSwitchMarkersAndNoFacadeFloor();

        $newMappingVersion = null;

        try {
            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            // Mirrors what MappingService::createNewMappingVersionFromMDTMapping() now does on a genuinely
            // first-ever import for a game version: clone the floor switch markers in from the facade source
            // mapping version before importMapPOIs() ever runs.
            $mappingService->copyMappingVersionContentsToDungeon($sourceMappingVersion, $newMappingVersion);
            $newMappingVersion->load('dungeonFloorSwitchMarkers');

            $preExistingMarkerCount = $newMappingVersion->dungeonFloorSwitchMarkers->count();
            $this->assertGreaterThan(
                0,
                $preExistingMarkerCount,
                'Sanity check on the fixture/clone: the new mapping version must already have floor switch markers for this test to prove anything.',
            );

            $mdtDungeon = $this->createMockPublic(MDTDungeon::class);
            $mdtDungeon->method('getMDTMapPOIs')->willReturn(collect([
                $this->createMapLinkMDTMapPOI($sourceFloor, $targetFloor),
            ]));

            // Act - simulates importMapPOIs() being called right after a genuinely first-ever import
            // ($currentMappingVersion === null), exactly like importMappingVersionFromMDT() does.
            $importMapPOIs = new ReflectionMethod($mappingImportService, 'importMapPOIs');
            $importMapPOIs->invokeArgs($mappingImportService, [
                null,
                $newMappingVersion,
                $mdtDungeon,
                $dungeon,
            ]);

            // Assert
            $this->assertSame(
                $preExistingMarkerCount,
                DungeonFloorSwitchMarker::query()->where('mapping_version_id', $newMappingVersion->id)->count(),
                'importMapPOIs() must not create a duplicate floor switch marker on top of ones already cloned into the new mapping version.',
            );
        } finally {
            $newMappingVersion?->delete();
        }
    }

    #[Test]
    public function importMapPOIs_givenNewMappingVersionHasNoFloorSwitchMarkersYet_stillCreatesOneFromMDTMapLinkPOI(): void
    {
        // Arrange
        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        [$dungeon, $sourceMappingVersion, $sourceFloor, $targetFloor] = $this->getDungeonWithFloorSwitchMarkersAndNoFacadeFloor();

        $newMappingVersion = null;

        try {
            // A bare mapping version with nothing cloned into it - the pre-#3762 default for a genuinely
            // first-ever import (e.g. a non-facade dungeon, which never had a facade source to clone from).
            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $newMappingVersion->load('dungeonFloorSwitchMarkers');

            $this->assertSame(
                0,
                $newMappingVersion->dungeonFloorSwitchMarkers->count(),
                'Sanity check on the fixture: the new mapping version must start with no floor switch markers.',
            );

            $mdtDungeon = $this->createMockPublic(MDTDungeon::class);
            $mdtDungeon->method('getMDTMapPOIs')->willReturn(collect([
                $this->createMapLinkMDTMapPOI($sourceFloor, $targetFloor),
            ]));

            // Act
            $importMapPOIs = new ReflectionMethod($mappingImportService, 'importMapPOIs');
            $importMapPOIs->invokeArgs($mappingImportService, [
                null,
                $newMappingVersion,
                $mdtDungeon,
                $dungeon,
            ]);

            // Assert
            $this->assertSame(
                1,
                DungeonFloorSwitchMarker::query()->where('mapping_version_id', $newMappingVersion->id)->count(),
                'importMapPOIs() must still fall back to creating a floor switch marker from MDT\'s own MapLink ' .
                'POI data when the new mapping version has none of its own yet.',
            );
        } finally {
            $newMappingVersion?->delete();
        }
    }

    private function createMapLinkMDTMapPOI(Floor $sourceFloor, Floor $targetFloor): MDTMapPOI
    {
        return new MDTMapPOI($sourceFloor->index, [
            'type'   => MDTMapPOIType::MapLink->value,
            'target' => $targetFloor->index,
            'x'      => 100.0,
            'y'      => 100.0,
        ]);
    }

    /**
     * A non-facade dungeon (so importMapPOIs()'s facade-location conversion is a no-op, keeping this test
     * focused on the duplication guard) whose newest mapping version has at least one floor switch marker
     * whose source/target floors have no `mdt_sub_level` override - so findFloorByMdtSubLevel() resolves them
     * by plain floor `index`, matching the fake MDTMapPOI built above.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: Floor, 3: Floor}
     */
    private function getDungeonWithFloorSwitchMarkersAndNoFacadeFloor(): array
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->with([
                'mappingVersions.dungeonFloorSwitchMarkers.floor',
                'mappingVersions.dungeonFloorSwitchMarkers.targetFloor',
                'floors',
            ])
            ->get()
            ->first(function (Dungeon $dungeon): bool {
                return $dungeon->getFacadeFloor() === null
                    && $this->findEligibleFloorSwitchMarker($dungeon) !== null;
            });

        if ($dungeon === null) {
            $this->fail('No non-facade dungeon found with a floor switch marker whose floors have no mdt_sub_level override.');
        }

        $mappingVersion = $dungeon->mappingVersions()->orderByDesc('id')->without('dungeon')->firstOrFail();
        $marker         = $this->findEligibleFloorSwitchMarker($dungeon);

        return [$dungeon, $mappingVersion, $marker->floor, $marker->targetFloor];
    }

    private function findEligibleFloorSwitchMarker(Dungeon $dungeon): ?DungeonFloorSwitchMarker
    {
        $newestMappingVersion = $dungeon->mappingVersions->sortByDesc('id')->first();

        if ($newestMappingVersion === null) {
            return null;
        }

        return $newestMappingVersion->dungeonFloorSwitchMarkers->first(
            static fn(DungeonFloorSwitchMarker $marker) => $marker->floor->mdt_sub_level === null
                && $marker->targetFloor->mdt_sub_level === null
                && $marker->floor->active
                && $marker->targetFloor->active,
        );
    }
}
