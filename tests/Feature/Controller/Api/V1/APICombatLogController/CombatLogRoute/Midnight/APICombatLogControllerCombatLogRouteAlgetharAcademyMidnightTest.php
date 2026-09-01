<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Midnight;
use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('AlgetharAcademyMidnight')]
class APICombatLogControllerCombatLogRouteAlgetharAcademyMidnightTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::ALGETH_AR_ACADEMY_MIDNIGHT->value;
    }

    #[Test]
    public function create_givenAlgetharAcademyMidnightPreseasonJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Midnight/midnight_s1_algethar_academy_preseason', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($postBody, $responseArr, 15, 521);
        $this->validateAffixes($responseArr);

        // #4144 - the Overgrown Ancient (boss npc 196482) died 172 yards from its mapped position, well outside
        // enemy_engagement_max_range - it must still get matched into its own pull instead of being dropped.
        $npcIdsInPulls = [];
        foreach ($responseArr['data']['pulls'] as $pull) {
            foreach ($pull['enemies'] as $enemy) {
                $npcIdsInPulls[] = $enemy['npcId'];
            }
        }
        $this->assertContains(196482, $npcIdsInPulls);
    }
}
