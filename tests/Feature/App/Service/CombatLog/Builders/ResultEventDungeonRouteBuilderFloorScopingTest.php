<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository;
use App\Repositories\Stub\KillZone\KillZoneRepository;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository;
use App\Service\CombatLog\Builders\ResultEventDungeonRouteBuilder;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\MapChange;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Naxxramas and its classic version list the same instance map, so their floors share ui_map_ids 162-167.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilder')]
final class ResultEventDungeonRouteBuilderFloorScopingTest extends PublicTestCase
{
    private const int UI_MAP_ID_NAXXRAMAS_FIRST = 162;

    private const int UI_MAP_ID_NAXXRAMAS_SECOND = 163;

    private const int UI_MAP_ID_GNOMEREGAN = 226;

    #[Test]
    #[DataProvider('naxxramasDungeonKeyProvider')]
    public function build_givenMapChangeToUiMapIdSharedWithAnotherDungeon_setsTheFloorOfTheRunsDungeon(string $dungeonKey): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();
        /** @var Collection<int, BaseResultEvent> $resultEvents */
        $resultEvents = collect([new MapChange($this->createMapChangeEvent(self::UI_MAP_ID_NAXXRAMAS_FIRST))]);
        $builder      = $this->createBuilder($dungeon, $resultEvents);

        // Act
        $builder->build();

        // Assert
        $this->assertNotNull($builder->finalFloor);
        $this->assertSame($dungeon->id, $builder->finalFloor->dungeon_id);
        $this->assertSame(self::UI_MAP_ID_NAXXRAMAS_FIRST, $builder->finalFloor->ui_map_id);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function naxxramasDungeonKeyProvider(): array
    {
        return [
            'Naxxramas'           => ['naxxramas'],
            'Naxxramas (classic)' => ['naxxramas_classic'],
        ];
    }

    #[Test]
    public function build_givenMapChangeToUiMapIdOfAnotherDungeonOnly_keepsTheCurrentFloor(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'naxxramas_classic')->firstOrFail();
        /** @var Collection<int, BaseResultEvent> $resultEvents */
        $resultEvents = collect([
            new MapChange($this->createMapChangeEvent(self::UI_MAP_ID_NAXXRAMAS_SECOND)),
            new MapChange($this->createMapChangeEvent(self::UI_MAP_ID_GNOMEREGAN)),
        ]);
        $builder = $this->createBuilder($dungeon, $resultEvents);

        // Act
        $builder->build();

        // Assert
        $this->assertNotNull($builder->finalFloor);
        $this->assertSame($dungeon->id, $builder->finalFloor->dungeon_id);
        $this->assertSame(self::UI_MAP_ID_NAXXRAMAS_SECOND, $builder->finalFloor->ui_map_id);
    }

    /**
     * Writes nothing: every write-side repository is a stub, and buildFinished() only records the current floor.
     *
     * @param  Collection<int, BaseResultEvent>                              $resultEvents
     * @return ResultEventDungeonRouteBuilder&object{finalFloor: Floor|null}
     */
    private function createBuilder(Dungeon $dungeon, Collection $resultEvents): ResultEventDungeonRouteBuilder
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion(null, null);
        $this->assertNotNull($mappingVersion);

        $dungeonRoute = new DungeonRoute(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $mappingVersion->id]);
        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);

        return new class(
            app(CoordinatesServiceInterface::class),
            new DungeonRouteRepository(),
            new KillZoneRepository(),
            new KillZoneEnemyRepository(),
            new KillZoneSpellRepository(),
            app(EnemyRepositoryInterface::class),
            app(NpcRepositoryInterface::class),
            $dungeonRoute,
            $resultEvents,
        ) extends ResultEventDungeonRouteBuilder {
            public ?Floor $finalFloor = null;

            #[\Override]
            protected function buildFinished(): void
            {
                $this->finalFloor = $this->currentFloor;
            }
        };
    }

    private function createMapChangeEvent(int $uiMapId): MapChangeEvent
    {
        $combatLogEntry = new CombatLogEntry(
            sprintf('3/25/2026 10:36:28.9051  MAP_CHANGE,%d,"Naxxramas",3615.830078,2886.610107,-2640.270020,-3734.100098', $uiMapId),
        );

        /** @var MapChangeEvent $mapChangeEvent */
        $mapChangeEvent = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        return $mapChangeEvent;
    }
}
