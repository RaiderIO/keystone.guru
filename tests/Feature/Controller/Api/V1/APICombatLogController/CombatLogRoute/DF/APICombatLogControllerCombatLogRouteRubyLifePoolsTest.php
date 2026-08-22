<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\DF;
use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('RubyLifePools')]
class APICombatLogControllerCombatLogRouteRubyLifePoolsTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::RUBY_LIFE_POOLS->value;
    }

    #[Test]
    public function create_givenRubyLifePoolsSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('DF/midnight_s2_ruby_life_pools', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->validateResponseStaticData($responseArr);
            $this->validateDungeon($responseArr);
            $this->validatePulls($responseArr, 15, 617);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
