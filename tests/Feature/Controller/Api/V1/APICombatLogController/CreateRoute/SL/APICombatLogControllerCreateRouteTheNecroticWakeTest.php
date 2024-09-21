<?php

namespace Controller\Api\V1\APICombatLogController\CreateRoute\SL;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CreateRoute\APICombatLogControllerCreateRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CreateRoute')]
#[Group('TheNecroticWake')]
class APICombatLogControllerCreateRouteTheNecroticWakeTest extends APICombatLogControllerCreateRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_NECROTIC_WAKE;
    }

    #[Test]
    public function create_givenTwwS1TheNecroticWake2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('SL/tww_s1_the_necrotic_wake_2', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 23, 347);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
