<?php

namespace Tests\Feature\Mapping;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
#[Group('Kalimdor')]
final class KalimdorMappingTest extends TestCase
{
    #[Test]
    public function floors_givenKalimdor_returnsEveryZoneWithBoundsAndOneFacade(): void
    {
        // Arrange
        $dungeon = $this->getKalimdor();

        // Act
        $realFloors   = $dungeon->floors->where('facade', false);
        $facadeFloors = $dungeon->floors->where('facade', true);

        // Assert
        $this->assertCount(23, $realFloors);
        $this->assertCount(1, $facadeFloors);
        $this->assertSame('dungeons.classic.kalimdor.floors.the_barrens', $dungeon->floors->firstWhere('default', true)->name);
        $this->assertSame($realFloors->count(), $realFloors->pluck('ui_map_id')->unique()->count());
        foreach ($dungeon->floors as $floor) {
            /** @var Floor $floor */
            $this->assertNotEquals($floor->ingame_min_x, $floor->ingame_max_x, $floor->name);
            $this->assertNotEquals($floor->ingame_min_y, $floor->ingame_max_y, $floor->name);
            if (!$floor->facade) {
                $this->assertGreaterThan(0, $floor->ui_map_id, $floor->name);
            }
        }
    }

    #[Test]
    public function mappingVersion_givenKalimdor_returnsInactiveForeverGameVersion(): void
    {
        // Arrange
        $dungeon = $this->getKalimdor();

        // Act
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        // Assert
        $this->assertFalse((bool)$gameVersion->active);
        $this->assertSame(Expansion::ALL[Expansion::EXPANSION_CLASSIC], $gameVersion->expansion_id);
        $this->assertNotNull($dungeon->getCurrentMappingVersionForGameVersion($gameVersion));
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenOrgrimmarOnDurotar_returnsTopOfTheMap(): void
    {
        // Arrange
        $durotar   = $this->getFloor('durotar');
        $orgrimmar = $this->getFloor('orgrimmar');

        // Act
        $latLng = $this->getFloorCenterOn($orgrimmar, $durotar);

        // Assert: Orgrimmar sits against Durotar's northern edge
        $this->assertGreaterThan(-64, $latLng->getLat());
        $this->assertGreaterThan(0, $latLng->getLng());
        $this->assertLessThan(384, $latLng->getLng());
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenZonesOnFacade_returnsNorthUpEastRight(): void
    {
        // Arrange
        $facade = $this->getKalimdor()->floors->firstWhere('facade', true);

        // Act
        $teldrassil = $this->getFloorCenterOn($this->getFloor('teldrassil'), $facade);
        $tanaris    = $this->getFloorCenterOn($this->getFloor('tanaris'), $facade);
        $durotar    = $this->getFloorCenterOn($this->getFloor('durotar'), $facade);
        $mulgore    = $this->getFloorCenterOn($this->getFloor('mulgore'), $facade);

        // Assert
        $this->assertGreaterThan($tanaris->getLat(), $teldrassil->getLat());
        $this->assertGreaterThan($mulgore->getLng(), $durotar->getLng());
    }

    private function getKalimdor(): Dungeon
    {
        return Dungeon::with('floors')->where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
    }

    private function getFloor(string $zoneKey): Floor
    {
        return $this->getKalimdor()->floors->firstWhere('name', sprintf('dungeons.classic.kalimdor.floors.%s', $zoneKey));
    }

    private function getFloorCenterOn(Floor $floor, Floor $onFloor): LatLng
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);

        return $coordinatesService->calculateMapLocationForIngameLocation(new IngameXY(
            ($floor->ingame_min_x + $floor->ingame_max_x) / 2,
            ($floor->ingame_min_y + $floor->ingame_max_y) / 2,
            $onFloor,
        ));
    }
}
