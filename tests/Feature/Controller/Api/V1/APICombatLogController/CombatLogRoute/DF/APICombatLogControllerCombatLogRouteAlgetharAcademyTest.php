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
        $postBody = $this->getJsonData('DF/df_s4_algethar_academy_bunten_no_roster_16_mv_9', self::FIXTURES_ROOT_DIR);

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

    #[Test]
    public function create_givenFortifiedOnlyAffixInPayload_shouldResolveAffixGroupByTimestamp(): void
    {
        // Arrange - payload reports only Fortified, simulating a non-max-level dungeon
        $postBody = $this->getJsonData('DF/df_s4_algethar_academy_fortified_only', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert - the route is still created and has an affix group resolved from the timestamp
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->assertNotEmpty($responseArr['data']['affixGroups'], 'Expected an affix group to be resolved by timestamp even when only one affix was reported');
    }
}
