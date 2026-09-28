<?php

namespace Tests\Feature\Mapping;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
#[Group('EasternKingdoms')]
final class EasternKingdomsMappingTest extends TestCase
{
    #[Test]
    public function floors_givenEasternKingdoms_returnsEveryZoneWithBoundsAndOneFacade(): void
    {
        // Arrange
        $dungeon = $this->getEasternKingdoms();

        // Act
        $realFloors   = $dungeon->floors->where('facade', false);
        $facadeFloors = $dungeon->floors->where('facade', true);

        // Assert
        $this->assertSame('dungeons.classic.eastern_kingdoms.abbreviation', $dungeon->abbreviation);
        $this->assertCount(26, $realFloors);
        $this->assertCount(1, $facadeFloors);
        $this->assertSame('dungeons.classic.eastern_kingdoms.floors.elwynn_forest', $dungeon->floors->firstWhere('default', true)->name);
        $this->assertSame(27, $facadeFloors->first()->index);
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
    public function mappingVersion_givenEasternKingdoms_returnsForeverGameVersionWithFacadeDisabled(): void
    {
        // Arrange
        $dungeon     = $this->getEasternKingdoms();
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        // Act
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        // Assert
        $this->assertNotNull($mappingVersion);
        $this->assertFalse((bool)$mappingVersion->facade_enabled);
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenIronforgeOnDunMorogh_returnsInsideTheMap(): void
    {
        // Arrange
        $dunMorogh = $this->getFloor('dun_morogh');
        $ironforge = $this->getFloor('ironforge');

        // Act
        $latLng = $this->getFloorCenterOn($ironforge, $dunMorogh);

        // Assert
        $this->assertLessThan(0, $latLng->getLat());
        $this->assertGreaterThan(-256, $latLng->getLat());
        $this->assertGreaterThan(0, $latLng->getLng());
        $this->assertLessThan(384, $latLng->getLng());
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenStormwindOnElwynnForest_returnsNorthWest(): void
    {
        // Arrange
        $elwynnForest  = $this->getFloor('elwynn_forest');
        $stormwindCity = $this->getFloor('stormwind_city');

        // Act
        $latLng = $this->getFloorCenterOn($stormwindCity, $elwynnForest);

        // Assert
        $this->assertGreaterThan(-128, $latLng->getLat());
        $this->assertLessThan(192, $latLng->getLng());
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenZonesOnFacade_returnsNorthUpEastRight(): void
    {
        // Arrange
        $facade = $this->getEasternKingdoms()->floors->firstWhere('facade', true);

        // Act
        $tirisfalGlades     = $this->getFloorCenterOn($this->getFloor('tirisfal_glades'), $facade);
        $stranglethornVale  = $this->getFloorCenterOn($this->getFloor('stranglethorn_vale'), $facade);
        $silverpineForest   = $this->getFloorCenterOn($this->getFloor('silverpine_forest'), $facade);
        $easternPlaguelands = $this->getFloorCenterOn($this->getFloor('eastern_plaguelands'), $facade);

        // Assert
        $this->assertGreaterThan($stranglethornVale->getLat(), $tirisfalGlades->getLat());
        $this->assertGreaterThan($silverpineForest->getLng(), $easternPlaguelands->getLng());
    }

    private function getEasternKingdoms(): Dungeon
    {
        return Dungeon::with('floors')->where('key', DungeonKey::EASTERN_KINGDOMS->value)->firstOrFail();
    }

    private function getFloor(string $zoneKey): Floor
    {
        return $this->getEasternKingdoms()->floors->firstWhere('name', sprintf('dungeons.classic.eastern_kingdoms.floors.%s', $zoneKey));
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
