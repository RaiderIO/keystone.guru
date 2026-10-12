<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Repositories\Database\Floor\FloorRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Magisters' Terrace (Midnight) maps two areas the party revisits in a Void phase as floors of their own that share
 * the original's ui_map_id: the terrace (2511, index 2 and 5) and the Grand Magister's Asylum (2516, index 3 and 4).
 */
#[Group('FloorRepository')]
final class FloorRepositoryTest extends PublicTestCase
{
    private const string MAGISTERS_TERRACE_FLOOR_PREFIX = 'dungeons.midnight.magisters_terrace_midnight.floors.';

    private const int UI_MAP_ID_ARCANE_ATHENEUM = 2515;

    private const int UI_MAP_ID_MAGISTERS_TERRACE = 2511;

    private const int UI_MAP_ID_GRAND_MAGISTER_ASYLUM = 2516;

    private const int UI_MAP_ID_DARKFLAME_CLEFT = 2303;

    private const int UI_MAP_ID_UNKNOWN = 999999;

    private FloorRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new FloorRepository();
    }

    #[Test]
    #[DataProvider('magistersTerraceVisitOrderProvider')]
    public function findByUiMapIdInVisitOrder_givenMagistersTerraceFloor_returnsFloorForThatPointInTheRun(
        ?string $currentFloorName,
        int     $uiMapId,
        string  $expectedFloorName,
    ): void {
        // Arrange
        $dungeon      = $this->getDungeon(DungeonKey::MAGISTERS_TERRACE_MIDNIGHT);
        $currentFloor = $currentFloorName === null ? null : $this->getMagistersTerraceFloor($currentFloorName);
        $expected     = $this->getMagistersTerraceFloor($expectedFloorName);

        // Act
        $result = $this->repository->findByUiMapIdInVisitOrder($uiMapId, $dungeon->id, $currentFloor);

        // Assert
        $this->assertSame($expected->id, $result?->id);
    }

    /**
     * @return array<string, array{0: string|null, 1: int, 2: string}>
     */
    public static function magistersTerraceVisitOrderProvider(): array
    {
        return [
            'start of the run, lowest index'                  => [null, self::UI_MAP_ID_MAGISTERS_TERRACE, 'magisters_terrace'],
            'arriving on the terrace from the Atheneum'       => ['arcane_atheneum', self::UI_MAP_ID_MAGISTERS_TERRACE, 'magisters_terrace'],
            'entering the Asylum from the terrace'            => ['magisters_terrace', self::UI_MAP_ID_GRAND_MAGISTER_ASYLUM, 'grand_magister_asylum'],
            'back on the terrace after the Asylum'            => ['grand_magister_asylum', self::UI_MAP_ID_MAGISTERS_TERRACE, 'magisters_terrace_void'],
            'back on the terrace after the Void Asylum'       => ['grand_magister_asylum_void', self::UI_MAP_ID_MAGISTERS_TERRACE, 'magisters_terrace_void'],
            'stepping back into the Asylum from the Void'     => ['magisters_terrace_void', self::UI_MAP_ID_GRAND_MAGISTER_ASYLUM, 'grand_magister_asylum_void'],
            'stepping back onto the terrace from the tower'   => ['tower_of_theory', self::UI_MAP_ID_MAGISTERS_TERRACE, 'magisters_terrace_void'],
            'single floor ui_map_id behind the current floor' => ['magisters_terrace_void', self::UI_MAP_ID_ARCANE_ATHENEUM, 'arcane_atheneum'],
        ];
    }

    #[Test]
    public function findByUiMapIdInVisitOrder_givenDungeonWithUniqueUiMapIds_returnsTheSameFloorAsFindByUiMapId(): void
    {
        // Arrange
        $dungeon      = $this->getDungeon(DungeonKey::DARKFLAME_CLEFT);
        $floors       = $dungeon->floors()->where('facade', false)->get();
        $currentFloor = $floors->last();
        $expected     = $this->repository->findByUiMapId(self::UI_MAP_ID_DARKFLAME_CLEFT, $dungeon->id);

        $this->assertNotNull($expected);
        $this->assertGreaterThan($expected->index, $currentFloor->index, 'Precondition: the party is past the floor it walks back onto');

        // Act
        $result = $this->repository->findByUiMapIdInVisitOrder(self::UI_MAP_ID_DARKFLAME_CLEFT, $dungeon->id, $currentFloor);

        // Assert
        $this->assertSame($expected->id, $result?->id);
    }

    #[Test]
    public function findByUiMapIdInVisitOrder_givenUnknownUiMapId_returnsNull(): void
    {
        // Arrange
        $dungeon      = $this->getDungeon(DungeonKey::MAGISTERS_TERRACE_MIDNIGHT);
        $currentFloor = $this->getMagistersTerraceFloor('grand_magister_asylum');

        // Act
        $result = $this->repository->findByUiMapIdInVisitOrder(self::UI_MAP_ID_UNKNOWN, $dungeon->id, $currentFloor);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function findByUiMapIdInVisitOrder_givenUiMapIdOfAnotherDungeon_returnsNull(): void
    {
        // Arrange
        $dungeon      = $this->getDungeon(DungeonKey::MAGISTERS_TERRACE_MIDNIGHT);
        $currentFloor = $this->getMagistersTerraceFloor('grand_magister_asylum');

        $this->assertNotNull(
            $this->repository->findByUiMapId(self::UI_MAP_ID_DARKFLAME_CLEFT),
            'Precondition: the ui_map_id belongs to a floor of another dungeon',
        );

        // Act
        $result = $this->repository->findByUiMapIdInVisitOrder(self::UI_MAP_ID_DARKFLAME_CLEFT, $dungeon->id, $currentFloor);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function findAllByUiMapId_givenUiMapIdZero_returnsEmptyCollection(): void
    {
        // Arrange
        $dungeon = $this->getDungeon(DungeonKey::MAGISTERS_TERRACE_MIDNIGHT);

        $this->assertTrue(
            $dungeon->floors()->where('ui_map_id', 0)->exists(),
            'Precondition: the dungeon has a floor without a ui_map_id',
        );

        // Act
        $result = $this->repository->findAllByUiMapId(0, $dungeon->id);

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    private function getDungeon(DungeonKey $dungeonKey): Dungeon
    {
        return Dungeon::query()->where('key', $dungeonKey->value)->firstOrFail();
    }

    private function getMagistersTerraceFloor(string $floorName): Floor
    {
        return Floor::query()
            ->where('name', sprintf('%s%s', self::MAGISTERS_TERRACE_FLOOR_PREFIX, $floorName))
            ->where('facade', false)
            ->firstOrFail();
    }
}
