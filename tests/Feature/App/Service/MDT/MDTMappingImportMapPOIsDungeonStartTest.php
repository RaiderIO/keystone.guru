<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTMapPOIType;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
#[Group('MappingVersion')]
#[Group('DungeonStart')]
final class MDTMappingImportMapPOIsDungeonStartTest extends PublicTestCase
{
    #[Test]
    public function importMapPOIs_givenDungeonEntranceAndNoDungeonStart_createsADungeonStart(): void
    {
        // Arrange
        [$dungeon, $sourceMappingVersion, $floor] = $this->getNonFacadeDungeonWithFloor();
        $latLng                                   = new LatLng(10.0, 20.0, $floor);
        $newMappingVersion                        = null;

        try {
            // A bare copy: none of the source's mapping, so there is no dungeon start yet
            $newMappingVersion = $this->app->make(MappingServiceInterface::class)->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $newMappingVersion->update(['facade_enabled' => false]);

            // Act
            $this->importMapPOIs($sourceMappingVersion, $newMappingVersion, $dungeon, $floor, $latLng);

            // Assert
            $dungeonStarts = DungeonStart::query()->where('mapping_version_id', $newMappingVersion->id)->get();
            $this->assertCount(1, $dungeonStarts);
            $this->assertSame($floor->id, $dungeonStarts->first()->floor_id);
            $this->assertEqualsWithDelta($latLng->getLat(), $dungeonStarts->first()->lat, 0.1);
            $this->assertEqualsWithDelta($latLng->getLng(), $dungeonStarts->first()->lng, 0.1);
            $this->assertFalse(
                MapIcon::query()
                    ->where('mapping_version_id', $newMappingVersion->id)
                    ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
                    ->exists(),
                'A dungeon entrance must no longer become a dungeon_start map icon.',
            );
        } finally {
            $newMappingVersion?->delete();
        }
    }

    #[Test]
    public function importMapPOIs_givenDungeonEntranceAndExistingDungeonStart_keepsTheExistingStartOnly(): void
    {
        // Arrange
        [$dungeon, $sourceMappingVersion, $floor] = $this->getNonFacadeDungeonWithFloor();
        $latLng                                   = new LatLng(10.0, 20.0, $floor);
        $newMappingVersion                        = null;

        try {
            $newMappingVersion = $this->app->make(MappingServiceInterface::class)->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $newMappingVersion->update(['facade_enabled' => false]);
            $existingStart = DungeonStart::factory()->create([
                'mapping_version_id' => $newMappingVersion->id,
                'floor_id'           => $floor->id,
                'lat'                => -50.0,
                'lng'                => 60.0,
            ]);

            // Act
            $this->importMapPOIs($sourceMappingVersion, $newMappingVersion, $dungeon, $floor, $latLng);

            // Assert
            $dungeonStartIds = DungeonStart::query()->where('mapping_version_id', $newMappingVersion->id)->pluck('id');
            $this->assertSame([$existingStart->id], $dungeonStartIds->all());
        } finally {
            $newMappingVersion?->delete();
        }
    }

    private function importMapPOIs(
        MappingVersion $currentMappingVersion,
        MappingVersion $newMappingVersion,
        Dungeon        $dungeon,
        Floor          $floor,
        LatLng         $latLng,
    ): void {
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $mdtCoordinate = Conversion::convertLatLngToMDTCoordinate($latLng);
        $mdtMapPOI     = new MDTMapPOI($floor->index, [
            'type' => MDTMapPOIType::DungeonEntrance->value,
            'x'    => $mdtCoordinate['x'],
            'y'    => $mdtCoordinate['y'],
        ]);

        $mdtDungeon = $this->createMockPublic(MDTDungeon::class);
        $mdtDungeon->method('getMDTMapPOIs')->willReturn(collect([$mdtMapPOI]));

        $importMapPOIs = new ReflectionMethod($mappingImportService, 'importMapPOIs');
        $importMapPOIs->invokeArgs($mappingImportService, [
            $currentMappingVersion,
            $newMappingVersion,
            $mdtDungeon,
            $dungeon,
        ]);
    }

    /**
     * A non-facade dungeon with a floor that has no `mdt_sub_level` override, so findFloorByMdtSubLevel()
     * resolves it by plain floor `index`, matching the fake MDTMapPOI.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: Floor}
     */
    private function getNonFacadeDungeonWithFloor(): array
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->with(['floors', 'mappingVersions'])
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => $dungeon->getFacadeFloor() === null
                && $dungeon->floors->contains(static fn(Floor $floor) => $floor->mdt_sub_level === null && $floor->active)
                && $dungeon->mappingVersions->isNotEmpty());

        if ($dungeon === null) {
            $this->fail('No non-facade dungeon found with a floor with no mdt_sub_level override.');
        }

        $mappingVersion = $dungeon->mappingVersions->sortByDesc('id')->first();
        $floor          = $dungeon->floors->first(static fn(Floor $floor) => $floor->mdt_sub_level === null && $floor->active);

        return [$dungeon, $mappingVersion, $floor];
    }
}
