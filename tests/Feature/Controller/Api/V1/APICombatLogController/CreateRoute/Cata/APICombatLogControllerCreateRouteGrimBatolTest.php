<?php

namespace Controller\Api\V1\APICombatLogController\CreateRoute\Cata;

use App\Models\Affix;
use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CreateRoute\APICombatLogControllerCreateRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CreateRoute')]
#[Group('GrimBatol')]
class APICombatLogControllerCreateRouteGrimBatolTest extends APICombatLogControllerCreateRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_GRIM_BATOL;
    }

    #[Test]
    public function create_givenTwwS1GrimBaytol2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Cata/tww_s1_grim_batol_2', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 27, 640);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    public function create_givenTwwS1GrimBatol4Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Cata/tww_s1_grim_batol_4', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 20, 645);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
