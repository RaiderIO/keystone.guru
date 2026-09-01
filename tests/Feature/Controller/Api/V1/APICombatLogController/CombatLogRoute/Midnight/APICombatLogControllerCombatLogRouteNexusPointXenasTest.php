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
#[Group('NexusPointXenas')]
class APICombatLogControllerCombatLogRouteNexusPointXenasTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::NEXUS_POINT_XENAS->value;
    }

    #[Test]
    public function create_givenNexusPointXenasPreseasonMv5Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s1_nexus_point_xenas_preseason_mv_5', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($postBody, $responseArr, 25, 562);
        $this->validateAffixes($responseArr);
    }
}
