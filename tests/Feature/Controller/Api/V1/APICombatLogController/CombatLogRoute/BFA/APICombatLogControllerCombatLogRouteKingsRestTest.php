<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\BFA;
use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
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
            $this->validatePulls($responseArr, 16, 661);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);

            // This run sends us all three of Zanazal's totems but not the Council of Tribes, and Reban but neither
            // King Dazar nor T'zala - and no Minion of Zul ever reaches the Shadow of Zul's pack. Every one of those
            // is awarded by KingsRestDespawningEnemiesRule, and lands in the pull of the enemy whose death triggered
            // it. The first totem to die also awards its two siblings, so all three totems land in the same pull as
            // the three bosses instead of the other two spawning a pull of their own.
            $this->validateNpcIdsInSamePull($responseArr, [
                NpcId::THUNDERING_TOTEM->value,
                NpcId::EXPLOSIVE_TOTEM->value,
                NpcId::TORRENT_TOTEM->value,
                NpcId::AKAALI_THE_CONQUEROR->value,
                NpcId::ZANAZAL_THE_WISE->value,
                NpcId::KULA_THE_BUTCHER->value,
            ]);
            $this->validateNpcIdsInSamePull($responseArr, [
                NpcId::REBAN->value,
                NpcId::SHADOW_OF_ZUL->value,
                NpcId::TZALA->value,
                NpcId::KING_DAZAR->value,
            ]);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
