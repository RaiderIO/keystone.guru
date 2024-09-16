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
#[Group('AraKaraCityOfEchoes')]
class APICombatLogControllerCreateRouteAraKaraCityOfEchoesTest extends APICombatLogControllerCreateRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES;
    }

    #[Test]
    public function create_givenTwwS1AraKaraCityOfEchoes3Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_ara_kara_city_of_echoes_3', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 22, 501);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    /**
     * This test was actually for a partially completed run. Group couldn't get past Avanoxx.
     * @return void
     */
    #[Test]
    public function create_givenTwwS1AraKaraCityOfEchoes6Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_ara_kara_city_of_echoes_6', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 14, 242);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
