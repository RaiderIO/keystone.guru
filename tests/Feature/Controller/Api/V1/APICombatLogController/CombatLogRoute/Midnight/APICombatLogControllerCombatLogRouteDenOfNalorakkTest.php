<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Midnight;
use App\Models\DungeonKey;
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
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->validateResponseStaticData($responseArr);
            $this->validateDungeon($responseArr);
            $this->validatePulls($postBody, $responseArr, 20, 740);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
