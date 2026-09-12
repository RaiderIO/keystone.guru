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
#[Group('WindrunnerSpire')]
class APICombatLogControllerCombatLogRouteWindrunnerSpireTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::WINDRUNNER_SPIRE->value;
    }

    #[Test]
    public function create_givenWindrunnerSpirePreseasonJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s1_windrunner_spire_preseason', self::FIXTURES_ROOT_DIR);

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($postBody, $responseArr, 28, 537);
        $this->validateAffixes($responseArr);
    }
}
