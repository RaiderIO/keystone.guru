<?php

namespace Controller\Api\V1\APICombatLogController\CombatLogRoute\Midnight;

use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('VoidscarArena')]
class APICombatLogControllerCombatLogRouteVoidscarArenaTest extends APICombatLogControllerCombatLogRouteTestBase
{
    /** @var int Taz'Rah - the first boss, mapped on floor 455 (index 2) */
    private const NPC_ID_TAZRAH = 238887;

    /** @var int Atroxus - the second boss */
    private const NPC_ID_ATROXUS = 239008;

    /** @var int Charonus - the final boss */
    private const NPC_ID_CHARONUS = 239167;

    /** @var int NPC 243988 is mapped on all three Voidscar floors, which is what makes it useful here */
    private const NPC_ID_STACKED_TRASH = 243988;

    /** @var int The NPC_ID_STACKED_TRASH enemy on floor 454 (index 1), at ingame 482.70/4443.30 */
    private const ENEMY_ID_TRASH_BEFORE_BOSS_FLOOR = 135553;

    /** @var int The closest NPC_ID_STACKED_TRASH enemy on floor 455 (index 2), ~94 yards away at 479.00/4537.49 */
    private const ENEMY_ID_TRASH_ON_BOSS_FLOOR = 135544;

    /** @var int ui_map_id of floor 455 - where both the boss and the trash kill below are logged */
    private const UI_MAP_ID_FLOOR_455 = 2572;

    protected function getDungeonKey(): string
    {
        return DungeonKey::VOIDSCAR_ARENA->value;
    }

    #[Test]
    public function create_givenVoidscarArenaSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s2_voidscar_arena', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->validateResponseStaticData($responseArr);
            $this->validateDungeon($responseArr);
            $this->validatePulls($responseArr, 17, 739);
            $this->validateAffixes($responseArr);

            // All three bosses were dropped from the route entirely while the floors' ingame coordinates were
            // assigned to the wrong floors - their mapped positions ended up hundreds of yards from where they
            // were actually killed, well outside enemy_engagement_max_range.
            foreach ([self::NPC_ID_TAZRAH, self::NPC_ID_ATROXUS, self::NPC_ID_CHARONUS] as $bossNpcId) {
                $this->assertNotNull(
                    $this->findResolvedEnemyId($responseArr, $bossNpcId),
                    sprintf('Boss NPC %d was not assigned to any pull', $bossNpcId),
                );
            }
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    /**
     * Voidscar Arena's floors 454 and 455 are stacked on top of one another - they occupy near identical ingame
     * X/Y - and the builder has no Z axis to tell them apart. A pull may therefore never contain enemies from
     * more than one floor: that is the signature of an enemy having been picked off the wrong floor.
     */
    #[Test]
    public function create_givenVoidscarArenaSeason2Json_shouldNotMixFloorsWithinASinglePull(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s2_voidscar_arena', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $dungeonRoute = $this->findDungeonRoute($responseArr);

            $this->assertCount(17, $dungeonRoute->killZones);

            foreach ($dungeonRoute->killZones as $killZone) {
                $floorIds = $this->getEnemiesOfKillZone($killZone->id)->pluck('floor_id')->unique();

                $this->assertCount(
                    1,
                    $floorIds,
                    sprintf('Pull %d contains enemies from multiple floors: %s', $killZone->index, $floorIds->implode(', ')),
                );
            }
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    /**
     * Killing Taz'Rah (floor 455, index 2) must take floor 454's enemies out of the running for everything that is
     * killed afterwards. NPC 243988 is mapped on all three floors, so a kill logged right on top of its floor-454
     * enemy only ever resolves to the floor-455 one because of the cutoff.
     */
    #[Test]
    public function create_givenAnNpcKilledOnAStackedFloorAfterABossDied_shouldNotResolveItToAnEnemyBeforeTheBossFloor(): void
    {
        // Arrange
        $postBody           = $this->getPostBodyWithoutNpcs();
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_TAZRAH, '000004BD4D', '20:15:16', '20:17:38', 451.78, 4535.17);
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_STACKED_TRASH, '000004BE99', '20:19:00', '20:19:30', 482.70, 4443.30);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->assertEquals(
                self::ENEMY_ID_TRASH_ON_BOSS_FLOOR,
                $this->findResolvedEnemyId($responseArr, self::NPC_ID_STACKED_TRASH),
            );
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    /**
     * The same kill without the preceding boss death still resolves to the floor-454 enemy it is standing on - which
     * is what makes the assertion above a test of the cutoff and not of the spatial matching.
     */
    #[Test]
    public function create_givenAnNpcKilledOnAStackedFloorWithoutABossDying_shouldResolveItToTheClosestEnemy(): void
    {
        // Arrange
        $postBody           = $this->getPostBodyWithoutNpcs();
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_STACKED_TRASH, '000004BE99', '20:19:00', '20:19:30', 482.70, 4443.30);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->assertEquals(
                self::ENEMY_ID_TRASH_BEFORE_BOSS_FLOOR,
                $this->findResolvedEnemyId($responseArr, self::NPC_ID_STACKED_TRASH),
            );
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    /**
     * The season 2 run's body, stripped of everything but its metadata - the two cutoff tests supply their own
     * handful of NPC kills so that the enemy they resolve to is unambiguous.
     *
     * @return array<string, mixed>
     */
    private function getPostBodyWithoutNpcs(): array
    {
        $postBody                 = $this->getJsonData('Midnight/midnight_s2_voidscar_arena', self::FIXTURES_ROOT_DIR);
        $postBody['npcs']         = [];
        $postBody['spells']       = [];
        $postBody['playerDeaths'] = [];

        return $postBody;
    }

    /**
     * @param array<string, mixed> $responseArr
     */
    private function findDungeonRoute(array $responseArr): DungeonRoute
    {
        return DungeonRoute::where('public_key', $responseArr['data']['publicKey'])->firstOrFail();
    }

    /**
     * @param array<string, mixed> $responseArr
     */
    private function findResolvedEnemyId(array $responseArr, int $npcId): ?int
    {
        foreach ($this->findDungeonRoute($responseArr)->killZones as $killZone) {
            $enemy = $this->getEnemiesOfKillZone($killZone->id)->firstWhere('npc_id', $npcId);

            if ($enemy !== null) {
                return $enemy->id;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, Enemy>
     */
    private function getEnemiesOfKillZone(int $killZoneId): Collection
    {
        return Enemy::query()
            ->join('kill_zone_enemies', 'kill_zone_enemies.enemy_id', '=', 'enemies.id')
            ->where('kill_zone_enemies.kill_zone_id', $killZoneId)
            ->select('enemies.*')
            ->get();
    }

    /**
     * @param array<string, mixed> $responseArr
     */
    private function deleteDungeonRoute(array $responseArr): void
    {
        DungeonRoute::where('public_key', $responseArr['data']['publicKey'])->first()?->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private static function npcEvent(int $npcId, string $spawnUid, string $engagedAt, string $diedAt, float $x, float $y): array
    {
        return [
            'npcId'     => $npcId,
            'spawnUid'  => $spawnUid,
            'engagedAt' => sprintf('2026-08-18T%s.000+00:00', $engagedAt),
            'diedAt'    => sprintf('2026-08-18T%s.000+00:00', $diedAt),
            'coord'     => ['x' => $x, 'y' => $y, 'uiMapId' => self::UI_MAP_ID_FLOOR_455],
        ];
    }
}
