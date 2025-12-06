<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\TWW;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('CityOfThreads')]
class APICombatLogControllerCombatLogRouteCityOfThreadsTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_CITY_OF_THREADS;
    }

    #[Test]
    public function create_givenTwwS1CityOfThreads3Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_city_of_threads_11', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        // Lacking 8 enemy forces due to missing Xeph'itik
        $this->validatePulls($responseArr, 17, 734);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
