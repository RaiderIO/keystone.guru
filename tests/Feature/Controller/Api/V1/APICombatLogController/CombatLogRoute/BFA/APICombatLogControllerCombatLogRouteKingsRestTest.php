<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\BFA;
use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('KingsRest')]
class APICombatLogControllerCombatLogRouteKingsRestTest extends APICombatLogControllerCombatLogRouteTestBase
{
    private const NPC_ID_EXPLOSIVE_TOTEM = 135764;

    private const NPC_ID_AKAALI_THE_CONQUEROR = 269808;

    private const NPC_ID_ZANAZAL_THE_WISE = 269810;

    private const NPC_ID_KULA_THE_BUTCHER = 269811;

    private const NPC_ID_SHADOW_OF_ZUL = 138489;

    private const NPC_ID_REBAN = 136984;

    private const NPC_ID_TZALA = 136976;

    private const NPC_ID_KING_DAZAR = 136160;

    protected function getDungeonKey(): string
    {
        return DungeonKey::KINGS_REST->value;
    }

    #[Test]
    public function create_givenKingsRestSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('BFA/midnight_s2_kings_rest', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->validateResponseStaticData($responseArr);
            $this->validateDungeon($responseArr);
            $this->validatePulls($responseArr, 18, 661);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);

            // This run sends us Zanazal's totems but not the Council of Tribes, and Reban but neither King Dazar nor
            // T'zala - and no Minion of Zul ever reaches the Shadow of Zul's pack. Every one of those is awarded by
            // KingsRestDespawningEnemiesRule, and lands in the pull of the enemy whose death triggered it.
            $this->validateNpcIdsInSamePull($responseArr, [
                self::NPC_ID_EXPLOSIVE_TOTEM,
                self::NPC_ID_AKAALI_THE_CONQUEROR,
                self::NPC_ID_ZANAZAL_THE_WISE,
                self::NPC_ID_KULA_THE_BUTCHER,
            ]);
            $this->validateNpcIdsInSamePull($responseArr, [
                self::NPC_ID_REBAN,
                self::NPC_ID_SHADOW_OF_ZUL,
                self::NPC_ID_TZALA,
                self::NPC_ID_KING_DAZAR,
            ]);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
