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
#[Group('TempleOfSethraliss')]
class APICombatLogControllerCombatLogRouteTempleOfSethralissTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::TEMPLE_OF_SETHRALISS->value;
    }

    #[Test]
    public function create_givenTempleOfSethralissSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('BFA/midnight_s2_temple_of_sethraliss', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->validateResponseStaticData($responseArr);
            $this->validateDungeon($responseArr);
            $this->validatePulls($responseArr, 20, 665);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
