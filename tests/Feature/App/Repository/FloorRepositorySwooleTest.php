<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Repositories\Swoole\FloorRepositorySwoole;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('FloorRepositorySwoole')]
final class FloorRepositorySwooleTest extends PublicTestCase
{
    private const string MAGISTERS_TERRACE_FLOOR_PREFIX = 'dungeons.midnight.magisters_terrace_midnight.floors.';

    private const int UI_MAP_ID_MAGISTERS_TERRACE = 2511;

    private FloorRepositorySwoole $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new FloorRepositorySwoole();
    }

    #[Test]
    public function findAllByUiMapId_calledTwice_returnsTheCachedCollection(): void
    {
        // Arrange
        $dungeon = $this->getMagistersTerrace();
        $first   = $this->repository->findAllByUiMapId(self::UI_MAP_ID_MAGISTERS_TERRACE, $dungeon->id);

        // Act
        $second = $this->repository->findAllByUiMapId(self::UI_MAP_ID_MAGISTERS_TERRACE, $dungeon->id);

        // Assert
        $this->assertCount(2, $first);
        $this->assertSame($first, $second);
    }

    #[Test]
    public function findByUiMapIdInVisitOrder_givenTerraceRevisitedAfterTheAsylum_returnsTheVoidTerrace(): void
    {
        // Arrange
        $dungeon      = $this->getMagistersTerrace();
        $currentFloor = $this->getMagistersTerraceFloor('grand_magister_asylum');
        $expected     = $this->getMagistersTerraceFloor('magisters_terrace_void');

        // Prime the cache with the start of the run, where the same ui_map_id resolves to the first terrace
        $this->repository->findByUiMapIdInVisitOrder(self::UI_MAP_ID_MAGISTERS_TERRACE, $dungeon->id, null);

        // Act
        $result = $this->repository->findByUiMapIdInVisitOrder(self::UI_MAP_ID_MAGISTERS_TERRACE, $dungeon->id, $currentFloor);

        // Assert
        $this->assertSame($expected->id, $result?->id);
    }

    private function getMagistersTerrace(): Dungeon
    {
        return Dungeon::query()->where('key', DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value)->firstOrFail();
    }

    private function getMagistersTerraceFloor(string $floorName): Floor
    {
        return Floor::query()
            ->where('name', sprintf('%s%s', self::MAGISTERS_TERRACE_FLOOR_PREFIX, $floorName))
            ->where('facade', false)
            ->firstOrFail();
    }
}
