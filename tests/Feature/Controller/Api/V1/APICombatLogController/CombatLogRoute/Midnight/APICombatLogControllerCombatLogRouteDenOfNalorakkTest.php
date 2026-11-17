<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Midnight;
use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('DenOfNalorakk')]
class APICombatLogControllerCombatLogRouteDenOfNalorakkTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::DEN_OF_NALORAKK->value;
    }

    #[Test]
    public function create_givenDenOfNalorakkSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s2_den_of_nalorakk', self::FIXTURES_ROOT_DIR);

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($postBody, $responseArr, 20, 726);
        $this->validateAffixes($responseArr);
        $this->validateBossesResolved($postBody, $responseArr);
    }

    /**
     * Fixture from run 25921524, generated with combatlog:outputcombatlogroutejson.
     */
    #[Test]
    public function create_givenNalorakkTurnedFriendlyAtOneHp_shouldContainNalorakkAndHisPack(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s2_den_of_nalorakk_nalorakk_turns_friendly', self::FIXTURES_ROOT_DIR);

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->validatePulls($postBody, $responseArr, 13, 731);
        $this->validateBossesResolved($postBody, $responseArr);
        /** @var array<int, array<string, mixed>> $pulls */
        $pulls          = $responseArr['data']['pulls'];
        $resolvedNpcIds = [];
        foreach ($pulls as $pull) {
            /** @var array<int, array<string, mixed>> $enemies */
            $enemies        = $pull['enemies'];
            $resolvedNpcIds = array_merge($resolvedNpcIds, array_column($enemies, 'npcId'));
        }
        foreach ([NpcId::NALORAKK, NpcId::ZULJARRA, NpcId::ECHO_OF_NALORAKK] as $npcId) {
            $this->assertContains($npcId->value, $resolvedNpcIds, sprintf('%s is not part of any pull', $npcId->name));
        }
    }
}
