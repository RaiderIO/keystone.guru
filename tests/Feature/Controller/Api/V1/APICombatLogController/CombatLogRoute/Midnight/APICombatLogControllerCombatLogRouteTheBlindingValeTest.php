<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Midnight;
use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('TheBlindingVale')]
class APICombatLogControllerCombatLogRouteTheBlindingValeTest extends APICombatLogControllerCombatLogRouteTestBase
{
    /** @var int Meittik - one of the three first bosses, fought at the near end of the bridge */
    private const NPC_ID_MEITTIK = 243028;

    /** @var int Lightwarden Ruia, the third boss - the party only walks underneath the bridge once she is dead */
    private const NPC_ID_LIGHTWARDEN_RUIA = 245912;

    /** @var int Ikuzz the Light Hunter, the second boss */
    private const NPC_ID_IKUZZ = 244887;

    /** @var int Mapped both in bridge group 46 and in group 49 just below it, which is what makes it useful here */
    private const NPC_ID_GROVEKEEPER = 245346;

    /**
     * @var int mdt_id of the NPC_ID_GROVEKEEPER enemy in bridge group 46, at ingame -1712.32/1324.16
     *
     * enemy_id is not usable here - it is reassigned every time the mapping is edited (MappingVersion
     * clones every enemy on save), whereas npc_id + mdt_id stay stable across mapping versions.
     */
    private const MDT_ID_GROVEKEEPER_ON_BRIDGE = 5;

    /** @var int mdt_id of the closest NPC_ID_GROVEKEEPER enemy outside the bridge groups, in group 49 ~37 yards away */
    private const MDT_ID_GROVEKEEPER_OFF_BRIDGE = 6;

    /** @var int ui_map_id of floor 408 - the only real floor of the dungeon, floor 459 is a facade */
    private const UI_MAP_ID_FLOOR_408 = 2500;

    protected function getDungeonKey(): string
    {
        return DungeonKey::THE_BLINDING_VALE->value;
    }

    #[Test]
    public function create_givenTheBlindingValeSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s2_the_blinding_vale', self::FIXTURES_ROOT_DIR);

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($postBody, $responseArr, 15, 685);
        $this->validateAffixes($responseArr);
        $this->validateBossesResolved($postBody, $responseArr);
    }

    /**
     * The bridge and the path underneath it share floor 408, near identical ingame X/Y and their npc_ids, so nothing
     * spatial tells them apart. Once Lightwarden Ruia is dead the party is past the bridge for good, so a kill logged
     * right on top of a bridge enemy must resolve to something off the bridge instead.
     */
    #[Test]
    public function create_givenAnNpcKilledOnTheBridgeAfterLightwardenRuiaDied_shouldNotResolveItToABridgeEnemy(): void
    {
        // Arrange
        $postBody           = $this->getPostBodyWithoutNpcs();
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_MEITTIK, '000014B001', '20:43:28', '20:45:32', -1741.79, 1491.81);
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_IKUZZ, '000014B002', '20:46:47', '20:49:11', -1737.49, 1227.90);
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_LIGHTWARDEN_RUIA, '000014B003', '20:50:47', '20:53:03', -1339.87, 1351.59);
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_GROVEKEEPER, '000014B004', '20:56:40', '20:56:45', -1712.00, 1324.00);

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->assertEquals(
            self::MDT_ID_GROVEKEEPER_OFF_BRIDGE,
            $this->findResolvedEnemyMdtId($responseArr, self::NPC_ID_GROVEKEEPER),
        );
    }

    /**
     * The same kill before Lightwarden Ruia dies still resolves to the bridge enemy it is standing on - which is what
     * makes the assertion above a test of the rule and not of the spatial matching.
     */
    #[Test]
    public function create_givenAnNpcKilledOnTheBridgeBeforeLightwardenRuiaDied_shouldResolveItToTheBridgeEnemy(): void
    {
        // Arrange
        $postBody           = $this->getPostBodyWithoutNpcs();
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_MEITTIK, '000014B001', '20:43:28', '20:45:32', -1741.79, 1491.81);
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_IKUZZ, '000014B002', '20:46:47', '20:49:11', -1737.49, 1227.90);
        $postBody['npcs'][] = self::npcEvent(self::NPC_ID_GROVEKEEPER, '000014B004', '20:56:40', '20:56:45', -1712.00, 1324.00);

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->assertEquals(
            self::MDT_ID_GROVEKEEPER_ON_BRIDGE,
            $this->findResolvedEnemyMdtId($responseArr, self::NPC_ID_GROVEKEEPER),
        );
    }

    /**
     * The season 2 run's body, stripped of everything but its metadata - the two tests above supply their own handful
     * of NPC kills so that the enemy they resolve to is unambiguous.
     *
     * @return array<string, mixed>
     */
    private function getPostBodyWithoutNpcs(): array
    {
        $postBody                 = $this->getJsonData('Midnight/midnight_s2_the_blinding_vale', self::FIXTURES_ROOT_DIR);
        $postBody['npcs']         = [];
        $postBody['spells']       = [];
        $postBody['playerDeaths'] = [];

        return $postBody;
    }

    /**
     * The API resource only exposes an npcId per pull enemy, so anything about which *enemy* (and therefore which
     * enemy pack) a kill resolved to has to be read back from the database.
     *
     * @param  array<string, mixed>      $responseArr
     * @return Collection<int, KillZone>
     */
    private function getKillZones(array $responseArr): Collection
    {
        return DungeonRoute::where('public_key', $responseArr['data']['publicKey'])
            ->firstOrFail()
            ->killZones()
            ->with('enemies')
            ->get();
    }

    /**
     * mdt_id (together with npc_id) is what stays stable across mapping versions - the enemy_id itself is
     * reassigned every time the mapping is edited, since MappingVersion clones every enemy on save.
     *
     * @param array<string, mixed> $responseArr
     */
    private function findResolvedEnemyMdtId(array $responseArr, int $npcId): ?int
    {
        foreach ($this->getKillZones($responseArr) as $killZone) {
            $enemy = $killZone->enemies->firstWhere('npc_id', $npcId);

            if ($enemy !== null) {
                return $enemy->mdt_id;
            }
        }

        return null;
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
            'coord'     => ['x' => $x, 'y' => $y, 'uiMapId' => self::UI_MAP_ID_FLOOR_408],
        ];
    }
}
