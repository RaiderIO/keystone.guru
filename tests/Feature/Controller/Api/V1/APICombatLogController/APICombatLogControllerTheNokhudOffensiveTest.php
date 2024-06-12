<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController;

use App\Models\Affix;
use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('NokhudOffensive')]
class APICombatLogControllerTheNokhudOffensiveTest extends APICombatLogControllerTestBase
{

    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE;
    }

    #[Test]
    public function create_givenTheNokhudOffensive14Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('df_s4_the_nokhud_offensive_14');

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

//        $responseArr = json_decode($response->content(), true);
//
//        dump($responseArr);
//
//        $this->validateResponseStaticData($responseArr);
//        $this->validateDungeon($responseArr);
//        $this->validatePulls($responseArr, 13, 450);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
