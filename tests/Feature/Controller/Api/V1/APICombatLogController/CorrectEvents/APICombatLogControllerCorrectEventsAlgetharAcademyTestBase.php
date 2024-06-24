<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents;

use App\Models\Affix;
use App\Models\Dungeon;
use Controller\Api\V1\APICombatLogController\APICombatLogControllerTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
class APICombatLogControllerCorrectEventsAlgetharAcademyTestBase extends APICombatLogControllerTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ALGETH_AR_ACADEMY;
    }

    #[Test]
    public function create_givenAlgetharAcademyBunten16Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('df_s4_algethar_academy_bunten_16');

        // Act
        $response = $this->post(route('api.v1.combatlog.event.correct'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

//        dd($responseArr);
//        $this->validateResponseStaticData($responseArr);
//        $this->validateDungeon($responseArr);
//        $this->validatePulls($responseArr, 13, 450);
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
