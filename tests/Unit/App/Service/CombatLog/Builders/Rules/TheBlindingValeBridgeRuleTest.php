<?php

namespace Tests\Unit\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Rules\TheBlindingValeBridgeRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('TheBlindingValeBridgeRule')]
class TheBlindingValeBridgeRuleTest extends PublicTestCase
{
    private const NPC_ID_LIGHTWARDEN_RUIA = 245912;

    #[Test]
    public function appliesToDungeon_givenTheBlindingVale_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::THE_BLINDING_VALE->value));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function appliesToDungeon_givenAnotherDungeon_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::VOIDSCAR_ARENA->value));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEnemyEligible_givenABridgeEnemyBeforeLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy(44));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('bridgeEnemyPackGroupProvider')]
    public function isEnemyEligible_givenABridgeEnemyAfterLightwardenRuiaDied_returnsFalse(int $group): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($group));

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function bridgeEnemyPackGroupProvider(): array
    {
        return [
            'group 44' => [44],
            'group 45' => [45],
            'group 46' => [46],
        ];
    }

    #[Test]
    public function isEnemyEligible_givenANonBridgeEnemyAfterLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy(79));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * An enemy that is not part of any pack has no group to block on, so it must never be excluded.
     */
    #[Test]
    public function isEnemyEligible_givenAnEnemyWithoutAnEnemyPackAfterLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy(null));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * The rule keys off the logged npc_id rather than the resolved Enemy precisely so that it still fires on a run
     * where Lightwarden Ruia never resolved to a mapped enemy.
     */
    #[Test]
    public function isEnemyEligible_givenLightwardenRuiaDiedWithoutResolvingToAnEnemy_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Assert
        $this->assertFalse($rule->isEnemyEligible($this->makeEnemy(44)));
    }

    #[Test]
    public function isEnemyEligible_givenAnotherNpcDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(244887, null);

        // Assert
        $this->assertTrue($rule->isEnemyEligible($this->makeEnemy(44)));
    }

    /**
     * The block is hard: it must survive the builder's retry for an NPC that matched nothing at all.
     */
    #[Test]
    public function hasActiveFirstPassExclusion_givenLightwardenRuiaDied_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->hasActiveFirstPassExclusion();

        // Assert
        $this->assertFalse($result);
    }

    private function makeRule(): TheBlindingValeBridgeRule
    {
        return new TheBlindingValeBridgeRule(new DungeonRouteBuilderLogging());
    }

    private function makeDungeon(string $key): Dungeon
    {
        $dungeon      = new Dungeon();
        $dungeon->key = $key;

        return $dungeon;
    }

    private function makeEnemy(?int $group): Enemy
    {
        $enemy = new Enemy();

        if ($group === null) {
            $enemy->enemy_pack_id = null;

            return $enemy;
        }

        $enemyPack        = new EnemyPack();
        $enemyPack->id    = 1;
        $enemyPack->group = $group;

        $enemy->enemy_pack_id = $enemyPack->id;
        $enemy->setRelation('enemyPack', $enemyPack);

        return $enemy;
    }
}
