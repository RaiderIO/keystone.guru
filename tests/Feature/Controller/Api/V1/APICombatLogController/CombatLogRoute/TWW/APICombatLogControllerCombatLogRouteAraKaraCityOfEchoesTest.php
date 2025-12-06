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
#[Group('AraKaraCityOfEchoes')]
class APICombatLogControllerCombatLogRouteAraKaraCityOfEchoesTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES;
    }

    #[Test]
    public function create_givenTwwS1AraKaraCityOfEchoesJah6Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_ara_kara_city_of_echoes_jah_6', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 27, 510);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    public function create_givenTwwS1AraKaraCityOfEchoes13Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_ara_kara_city_of_echoes_13', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 15, 498);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    public function create_givenTwwS3PtrAraKaraCityOfEchoes7Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s3_ptr_ara_kara_city_of_echoes_7', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 20, 811);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
