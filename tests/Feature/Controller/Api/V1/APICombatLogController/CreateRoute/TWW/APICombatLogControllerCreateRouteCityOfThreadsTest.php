<?php

namespace Controller\Api\V1\APICombatLogController\CreateRoute\TWW;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CreateRoute\APICombatLogControllerCreateRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CreateRoute')]
#[Group('CityOfThreads')]
class APICombatLogControllerCreateRouteCityOfThreadsTest extends APICombatLogControllerCreateRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_CITY_OF_THREADS;
    }

    #[Test]
    public function create_givenTwwS1CityOfThreads3Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_city_of_threads_3', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        dump($responseArr);

//        $this->validateResponseStaticData($responseArr);
//        $this->validateDungeon($responseArr);
//        $this->validatePulls($responseArr, 13, 450);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
