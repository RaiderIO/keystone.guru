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

    private const NPC_ID_LIGHTFEATHER_PETALWING = 245484;

    /** @var int The mdt_id of the Lightfeather Petalwing underneath the bridge, the closest one to bridge group 46 */
    private const MDT_ID_UNDER_BRIDGE_PETALWING = 7;

    /** @var int Ikuzz the Light Hunter, an ungrouped enemy the rule does not name */
    private const NPC_ID_UNRELATED = 244887;

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
     * An enemy that is not part of any pack has no group to block on, and is not one the rule names by unique key,
     * so it must never be excluded.
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

    #[Test]
    public function isEnemyEligible_givenAnEnemyWithoutAnEnemyPackBeforeLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy(null));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * MDT groups neither of the two traversals' Lightfeather Petalwings, so the one underneath the bridge cannot be
     * blocked by its pack the way every other under-bridge enemy is.
     */
    #[Test]
    public function isEnemyEligible_givenAnUngroupedUnderBridgeEnemyBeforeLightwardenRuiaDied_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeUngroupedEnemy(self::NPC_ID_LIGHTFEATHER_PETALWING, self::MDT_ID_UNDER_BRIDGE_PETALWING));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEnemyEligible_givenAnUngroupedUnderBridgeEnemyAfterLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeUngroupedEnemy(self::NPC_ID_LIGHTFEATHER_PETALWING, self::MDT_ID_UNDER_BRIDGE_PETALWING));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * The other two ungrouped Petalwings sit further from the bridge and were never excluded, so keying on the npc id
     * alone would block them too.
     */
    #[Test]
    public function isEnemyEligible_givenAnUngroupedEnemySharingTheNpcIdBeforeLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeUngroupedEnemy(self::NPC_ID_LIGHTFEATHER_PETALWING, 5));

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

    /**
     * The packs underneath the bridge only spawn once Ruia is dead, so nothing on the way to Ikuzz may match them.
     */
    #[Test]
    #[DataProvider('underBridgeEnemyPackGroupProvider')]
    public function isEnemyEligible_givenAnUnderBridgeEnemyBeforeLightwardenRuiaDied_returnsFalse(int $group): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($group));

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function underBridgeEnemyPackGroupProvider(): array
    {
        return [
            'group 47' => [47],
            'group 48' => [48],
            'group 49' => [49],
            'group 50' => [50],
            'group 54' => [54],
        ];
    }

    #[Test]
    #[DataProvider('underBridgeEnemyPackGroupProvider')]
    public function isEnemyEligible_givenAnUnderBridgeEnemyAfterLightwardenRuiaDied_returnsTrue(int $group): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($group));

        // Assert
        $this->assertTrue($result);
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

    private function makeUngroupedEnemy(int $npcId, int $mdtId): Enemy
    {
        $enemy                = new Enemy();
        $enemy->enemy_pack_id = null;
        $enemy->npc_id        = $npcId;
        $enemy->mdt_id        = $mdtId;

        return $enemy;
    }

    private function makeEnemy(?int $group): Enemy
    {
        if ($group === null) {
            return $this->makeUngroupedEnemy(self::NPC_ID_UNRELATED, 1);
        }

        $enemy = new Enemy();

        $enemyPack        = new EnemyPack();
        $enemyPack->id    = 1;
        $enemyPack->group = $group;

        $enemy->enemy_pack_id = $enemyPack->id;
        $enemy->setRelation('enemyPack', $enemyPack);

        return $enemy;
    }
}
