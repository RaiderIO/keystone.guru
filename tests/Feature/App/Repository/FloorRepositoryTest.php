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

    private const int UI_MAP_ID_NAXXRAMAS = 162;

    private const int UI_MAP_ID_GNOMEREGAN = 226;

    private const string DUNGEON_KEY_NAXXRAMAS = 'naxxramas';

    private const string DUNGEON_KEY_NAXXRAMAS_CLASSIC = 'naxxramas_classic';

    private const string DUNGEON_KEY_GNOMEREGAN = 'gnomeregan';

    private const string DUNGEON_KEY_GNOMEREGAN_SOD = 'gnomeregan_sod';

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

        $this->assertTrue(
            $this->repository->findAllByUiMapIdInAnyDungeon(self::UI_MAP_ID_DARKFLAME_CLEFT)->isNotEmpty(),
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

    #[Test]
    #[DataProvider('sharedUiMapIdProvider')]
    public function findByUiMapId_givenUiMapIdSharedWithAnotherDungeon_returnsTheFloorOfTheGivenDungeon(
        int    $uiMapId,
        string $dungeonKey,
        string $otherDungeonKey,
    ): void {
        // Arrange
        $dungeon      = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();
        $otherDungeon = Dungeon::query()->where('key', $otherDungeonKey)->firstOrFail();

        $this->assertTrue(
            $otherDungeon->floors()->where('ui_map_id', $uiMapId)->exists(),
            'Precondition: the other dungeon carries the same ui_map_id',
        );

        // Act
        $result = $this->repository->findByUiMapId($uiMapId, $dungeon->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($dungeon->id, $result->dungeon_id);
        $this->assertSame($uiMapId, $result->ui_map_id);
    }

    /**
     * Both directions per pair: an unscoped lookup returns the lowest floor id, which is right for one of the two.
     *
     * @return array<string, array{0: int, 1: string, 2: string}>
     */
    public static function sharedUiMapIdProvider(): array
    {
        return [
            'Naxxramas'                        => [self::UI_MAP_ID_NAXXRAMAS, self::DUNGEON_KEY_NAXXRAMAS, self::DUNGEON_KEY_NAXXRAMAS_CLASSIC],
            'Naxxramas (classic)'              => [self::UI_MAP_ID_NAXXRAMAS, self::DUNGEON_KEY_NAXXRAMAS_CLASSIC, self::DUNGEON_KEY_NAXXRAMAS],
            'Gnomeregan'                       => [self::UI_MAP_ID_GNOMEREGAN, self::DUNGEON_KEY_GNOMEREGAN, self::DUNGEON_KEY_GNOMEREGAN_SOD],
            'Gnomeregan (Season of Discovery)' => [self::UI_MAP_ID_GNOMEREGAN, self::DUNGEON_KEY_GNOMEREGAN_SOD, self::DUNGEON_KEY_GNOMEREGAN],
        ];
    }

    #[Test]
    public function findByUiMapId_givenUiMapIdOfAnotherDungeonOnly_returnsNull(): void
    {
        // Arrange
        $dungeon = $this->getDungeon(DungeonKey::MAGISTERS_TERRACE_MIDNIGHT);

        $this->assertTrue(
            $this->repository->findAllByUiMapIdInAnyDungeon(self::UI_MAP_ID_NAXXRAMAS)->isNotEmpty(),
            'Precondition: the ui_map_id belongs to a floor of another dungeon',
        );

        // Act
        $result = $this->repository->findByUiMapId(self::UI_MAP_ID_NAXXRAMAS, $dungeon->id);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function findAllByUiMapIdInAnyDungeon_givenSharedUiMapId_returnsTheFloorOfEachDungeon(): void
    {
        // Arrange
        $expectedDungeonIds = Dungeon::query()
            ->whereIn('key', [self::DUNGEON_KEY_NAXXRAMAS, self::DUNGEON_KEY_NAXXRAMAS_CLASSIC])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        // Act
        $result = $this->repository->findAllByUiMapIdInAnyDungeon(self::UI_MAP_ID_NAXXRAMAS);

        // Assert
        $this->assertSame($expectedDungeonIds, $result->pluck('dungeon_id')->sort()->values()->all());
        $this->assertSame([self::UI_MAP_ID_NAXXRAMAS], $result->pluck('ui_map_id')->unique()->values()->all());
    }

    #[Test]
    public function findAllByUiMapIdInAnyDungeon_givenUiMapIdZero_returnsEmptyCollection(): void
    {
        // Arrange
        $this->assertTrue(
            Floor::query()->where('ui_map_id', 0)->exists(),
            'Precondition: some floor carries no ui_map_id',
        );

        // Act
        $result = $this->repository->findAllByUiMapIdInAnyDungeon(0);

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
