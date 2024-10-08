<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CreateRoute\DF;

use App\Models\Affix;
use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CreateRoute\APICombatLogControllerCreateRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CreateRoute')]
#[Group('NokhudOffensive')]
class APICombatLogControllerCreateRouteTheNokhudOffensiveTest extends APICombatLogControllerCreateRouteTestBase
{

    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE;
    }

    #[Test]
    public function create_givenTheNokhudOffensive14Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('DF/df_s4_the_nokhud_offensive_14', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 21, 494); // This route just doesn't match count for some reason
        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    public function create_givenTheNokhudOffensive8Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('DF/df_s4_the_nokhud_offensive_8', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 24, 528);
        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_ENTANGLING, Affix::AFFIX_BOLSTERING);
    }
}
