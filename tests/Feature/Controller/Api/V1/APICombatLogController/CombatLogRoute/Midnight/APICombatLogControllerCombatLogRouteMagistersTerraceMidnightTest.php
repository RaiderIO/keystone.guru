<?php

namespace Controller\Api\V1\APICombatLogController\CombatLogRoute\Midnight;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('MagistersTerraceMidnight')]
class APICombatLogControllerCombatLogRouteMagistersTerraceMidnightTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_MAGISTERS_TERRACE_MIDNIGHT;
    }

    #[Test]
    public function create_givenMagistersTerraceMidnightPreseasonJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s1_magisters_terrace_preseason', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 26, 587);
        $this->validateAffixes($responseArr);
    }
}
