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
            $this->validatePulls($responseArr, 18, 631);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
