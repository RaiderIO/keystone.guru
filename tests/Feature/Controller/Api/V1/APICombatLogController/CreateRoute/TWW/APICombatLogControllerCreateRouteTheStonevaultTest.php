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
#[Group('TheStonevault')]
class APICombatLogControllerCreateRouteTheStonevaultTest extends APICombatLogControllerCreateRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_STONEVAULT;
    }

    #[Test]
    public function create_givenTwwS1TheStonevault4Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_the_stonevault_4', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    /**
     * This run was never finished.
     *
     * @return void
     */
    #[Test]
    public function create_givenTwwS1TheStonevault6Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('TWW/tww_s1_the_stonevault_6', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 4, 131);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
