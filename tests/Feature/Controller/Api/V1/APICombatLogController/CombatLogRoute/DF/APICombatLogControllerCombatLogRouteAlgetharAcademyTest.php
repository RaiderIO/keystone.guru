<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\DF;

use App\Models\Affix;
use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('AlgetharAcademy')]
class APICombatLogControllerCombatLogRouteAlgetharAcademyTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ALGETH_AR_ACADEMY;
    }

    #[Test]
    public function create_givenAlgetharAcademyBunten16Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('DF/df_s4_algethar_academy_bunten_no_roster_16', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 13, 450);
        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
