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
#[Group('MistsOfTirnaScithe')]
class APICombatLogControllerCreateRouteMistsOfTirnaScitheTest extends APICombatLogControllerCreateRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE;
    }

    #[Test]
    public function create_givenTwwS1MistsOfTirnaScithe2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('SL/tww_s1_mists_of_tirna_scithe_2', self::FIXTURES_ROOT_DIR);

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

    #[Test]
    public function create_givenTwwS1MistsOfTirnaScithe4Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('SL/tww_s1_mists_of_tirna_scithe_4', self::FIXTURES_ROOT_DIR);

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