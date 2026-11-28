<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Logic\Structs\IngameXY;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Repositories\Stub\DungeonRoute\DungeonRouteAffixGroupRepository;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository;
use App\Repositories\Stub\KillZone\KillZoneRepository;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository;
use App\Service\CombatLog\Builders\CombatLogRouteCorrectionBuilder;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Both halves of Dawn of the Infinite list the same instance map, so their floors share ui_map_ids; Galakrond's Fall
 * holds the lower floor ids, which an unscoped lookup returns for a Murozond's Rise run.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilder')]
final class CombatLogRouteCorrectionBuilderFloorScopingTest extends PublicTestCase
{
    private const int UI_MAP_ID_DAWN_OF_THE_INFINITE = 2191;

    private const int UI_MAP_ID_NAXXRAMAS = 162;

    #[Test]
    public function getCombatLogRoute_givenEventsOnUiMapIdSharedWithAnotherDungeon_measuresThemOnTheRunsOwnFloor(): void
    {
        // Arrange
        $dungeon       = $this->getDungeon(DungeonKey::DAWN_OF_THE_INFINITE_MUROZONDS_RISE);
        $expectedFloor = $dungeon->floors()->where('ui_map_id', self::UI_MAP_ID_DAWN_OF_THE_INFINITE)->firstOrFail();
        $otherFloor    = $this->getDungeon(DungeonKey::DAWN_OF_THE_INFINITE_GALAKRONDS_FALL)
            ->floors()->where('ui_map_id', self::UI_MAP_ID_DAWN_OF_THE_INFINITE)->firstOrFail();
        $this->assertLessThan($expectedFloor->id, $otherFloor->id, 'Precondition: the other dungeon\'s floor comes first unscoped');

        $enemy              = $this->getResolvableEnemy($dungeon, $expectedFloor);
        $coordinatesService = $this->createGridFloorRecordingCoordinatesService();
        $combatLogRoute     = $this->createCombatLogRoute($dungeon, $enemy, self::UI_MAP_ID_DAWN_OF_THE_INFINITE);
        $builder            = $this->createBuilder($combatLogRoute, $coordinatesService);
        $builder->build();

        // Act
        $result = $builder->getCombatLogRoute();

        // Assert
        $this->assertNotNull($combatLogRoute->npcs->first()?->getResolvedEnemy(), 'Precondition: the npc resolves to an enemy');
        $this->assertCount(1, $result->spells);
        $this->assertCount(1, $result->playerDeaths);
        $this->assertSame(
            // Npc player location, npc enemy location, spell, player death
            [$expectedFloor->id, $expectedFloor->id, $expectedFloor->id, $expectedFloor->id],
            $coordinatesService->gridFloorIds,
        );
    }

    #[Test]
    public function getCombatLogRoute_givenEventsOnUiMapIdOfAnotherDungeonOnly_skipsTheSpellAndPlayerDeath(): void
    {
        // Arrange
        $dungeon = $this->getDungeon(DungeonKey::DAWN_OF_THE_INFINITE_MUROZONDS_RISE);
        $floor   = $dungeon->floors()->where('ui_map_id', self::UI_MAP_ID_DAWN_OF_THE_INFINITE)->firstOrFail();
        $this->assertTrue(
            Floor::query()->where('ui_map_id', self::UI_MAP_ID_NAXXRAMAS)->exists(),
            'Precondition: another dungeon carries the ui_map_id',
        );

        $enemy          = $this->getResolvableEnemy($dungeon, $floor);
        $combatLogRoute = $this->createCombatLogRoute($dungeon, $enemy, self::UI_MAP_ID_NAXXRAMAS);
        $builder        = $this->createBuilder($combatLogRoute, app(CoordinatesServiceInterface::class));
        $builder->build();

        // Act
        $result = $builder->getCombatLogRoute();

        // Assert
        $this->assertCount(1, $result->npcs, 'Precondition: the npc is still corrected');
        $this->assertCount(0, $result->spells);
        $this->assertCount(0, $result->playerDeaths);
    }

    private function getDungeon(DungeonKey $dungeonKey): Dungeon
    {
        return Dungeon::query()->where('key', $dungeonKey->value)->firstOrFail();
    }

    private function getResolvableEnemy(Dungeon $dungeon, Floor $floor): Enemy
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion(null, null);
        $this->assertNotNull($mappingVersion);

        return Enemy::query()
            ->with('floor')
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('floor_id', $floor->id)
            ->whereNull('teeming')
            ->whereIn('npc_id', app(NpcRepositoryInterface::class)->getInUseNpcIds($mappingVersion))
            ->firstOrFail();
    }

    /**
     * @return CoordinatesService&object{gridFloorIds: array<int, int|null>}
     */
    private function createGridFloorRecordingCoordinatesService(): CoordinatesService
    {
        return new class extends CoordinatesService {
            /** @var array<int, int|null> */
            public array $gridFloorIds = [];

            #[\Override]
            public function calculateGridLocationForIngameLocation(IngameXY $ingameXY, int $gridSizeX, int $gridSizeY): IngameXY
            {
                $this->gridFloorIds[] = $ingameXY->getFloor()?->id;

                return parent::calculateGridLocationForIngameLocation($ingameXY, $gridSizeX, $gridSizeY);
            }
        };
    }

    /**
     * One kill at the enemy's exact ingame location, and one spell and one player death logged on $eventUiMapId.
     */
    private function createCombatLogRoute(Dungeon $dungeon, Enemy $enemy, int $eventUiMapId): CombatLogRouteRequestDto
    {
        $ingameXY = app(CoordinatesServiceInterface::class)->calculateIngameLocationForMapLocation($enemy->getLatLng());
        $start    = Carbon::parse('2026-01-01T12:00:00.000+00:00');
        $format   = CombatLogRouteRequestDto::DATE_TIME_FORMAT;
        $coord    = [
            'x'       => $ingameXY->getX(),
            'y'       => $ingameXY->getY(),
            'uiMapId' => $eventUiMapId,
        ];

        return CombatLogRouteRequestDto::createFromArray([
            'metadata' => [
                'runId'         => 'dawn-of-the-infinite-floor-scoping',
                'keystoneRunId' => 98765,
                'loggedRunId'   => 87654,
                'period'        => 99,
                'season'        => 'season-test',
                'regionId'      => 2,
                'realmType'     => 'live',
                'wowInstanceId' => $dungeon->map_id,
            ],
            'settings' => [
                'temporary'      => true,
                'debugIcons'     => false,
                'mappingVersion' => null,
            ],
            'challengeMode' => [
                'start'           => $start->format($format),
                'end'             => $start->copy()->addMinutes(30)->format($format),
                'success'         => true,
                'durationMs'      => 1800000,
                'parTimeMs'       => 1800000,
                'timerFraction'   => 1,
                'challengeModeId' => $dungeon->challenge_mode_id,
                'level'           => 10,
                'numDeaths'       => 1,
                'affixes'         => null,
            ],
            'roster' => [
                'numMembers'       => 5,
                'averageItemLevel' => 600,
                'characterIds'     => [0, 1, 2, 3, 4],
                'specIds'          => [105, 72, 72, 72, 73],
                'classIds'         => [11, 1, 1, 1, 1],
            ],
            'npcs' => [
                [
                    'npcId'     => $enemy->npc_id,
                    'spawnUid'  => '0000000001',
                    'engagedAt' => $start->copy()->addMinute()->format($format),
                    'diedAt'    => $start->copy()->addMinute()->addSeconds(10)->format($format),
                    'coord'     => [
                        'x'       => $ingameXY->getX(),
                        'y'       => $ingameXY->getY(),
                        'uiMapId' => self::UI_MAP_ID_DAWN_OF_THE_INFINITE,
                    ],
                ],
            ],
            'spells' => [
                [
                    'spellId'   => 2825,
                    'playerUid' => 'Player-0000-00000001',
                    'castAt'    => $start->copy()->addMinute()->addSeconds(2)->format($format),
                    'coord'     => $coord,
                ],
            ],
            'playerDeaths' => [
                [
                    'characterId' => 1,
                    'classId'     => 1,
                    'specId'      => 72,
                    'itemLevel'   => 600,
                    'diedAt'      => $start->copy()->addMinute()->addSeconds(5)->format($format),
                    'coord'       => $coord,
                ],
            ],
        ]);
    }

    /**
     * Writes nothing: every write-side repository is a stub.
     */
    private function createBuilder(
        CombatLogRouteRequestDto    $combatLogRoute,
        CoordinatesServiceInterface $coordinatesService,
    ): CombatLogRouteCorrectionBuilder {
        return new CombatLogRouteCorrectionBuilder(
            app(SeasonServiceInterface::class),
            app(SeasonAffixGroupServiceInterface::class),
            $coordinatesService,
            new DungeonRouteRepository(),
            new DungeonRouteAffixGroupRepository(),
            new KillZoneRepository(),
            new KillZoneEnemyRepository(),
            new KillZoneSpellRepository(),
            app(EnemyRepositoryInterface::class),
            app(NpcRepositoryInterface::class),
            app(SpellRepositoryInterface::class),
            app(FloorRepositoryInterface::class),
            app(DungeonRepositoryInterface::class),
            $combatLogRoute,
        );
    }
}
