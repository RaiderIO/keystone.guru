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

    /** @var string An enemy the rule names on neither side of Ruia's death */
    private const UNIQUE_KEY_UNRELATED = '244887-1';

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
    #[DataProvider('bridgeEnemyUniqueKeyProvider')]
    public function isEnemyEligible_givenABridgeEnemyBeforeLightwardenRuiaDied_returnsTrue(string $uniqueKey): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($uniqueKey));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('bridgeEnemyUniqueKeyProvider')]
    public function isEnemyEligible_givenABridgeEnemyAfterLightwardenRuiaDied_returnsFalse(string $uniqueKey): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($uniqueKey));

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function bridgeEnemyUniqueKeyProvider(): array
    {
        return self::uniqueKeyProvider(['245339-11', '245345-25', '254850-10', '245410-90', '245346-5', '245484-16']);
    }

    /**
     * The enemies underneath the bridge only spawn once Ruia is dead, so nothing on the way to Ikuzz may match them.
     */
    #[Test]
    #[DataProvider('underBridgeEnemyUniqueKeyProvider')]
    public function isEnemyEligible_givenAnUnderBridgeEnemyBeforeLightwardenRuiaDied_returnsFalse(string $uniqueKey): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($uniqueKey));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('underBridgeEnemyUniqueKeyProvider')]
    public function isEnemyEligible_givenAnUnderBridgeEnemyAfterLightwardenRuiaDied_returnsTrue(string $uniqueKey): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy($uniqueKey));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function underBridgeEnemyUniqueKeyProvider(): array
    {
        return self::uniqueKeyProvider([
            '245410-107', '245345-28', '245346-6', '245336-1', '245345-10',
            // The Lightfeather Petalwings, which MDT groups on some mapping versions and not on others
            '245484-5', '245484-6', '245484-7',
        ]);
    }

    /**
     * The rule matches on the enemy's own key, so its pack - or the absence of one - must not change the outcome.
     */
    #[Test]
    public function isEnemyEligible_givenAnUnderBridgeEnemyInAPackBeforeLightwardenRuiaDied_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemyInPack('245484-7', 57));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEnemyEligible_givenAnUnderBridgeEnemyInAPackAfterLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemyInPack('245484-7', 57));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * The Lightfeather Petalwings elsewhere in the dungeon must stay eligible, so keying on the npc id alone would
     * block the wrong enemies.
     */
    #[Test]
    public function isEnemyEligible_givenAnEnemySharingTheNpcIdBeforeLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy('245484-1'));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isEnemyEligible_givenAnUnrelatedEnemyAfterLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_LIGHTWARDEN_RUIA, null);

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy(self::UNIQUE_KEY_UNRELATED));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isEnemyEligible_givenAnUnrelatedEnemyBeforeLightwardenRuiaDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->isEnemyEligible($this->makeEnemy(self::UNIQUE_KEY_UNRELATED));

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
        $this->assertFalse($rule->isEnemyEligible($this->makeEnemy('245339-11')));
    }

    #[Test]
    public function isEnemyEligible_givenAnotherNpcDied_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(244887, null);

        // Assert
        $this->assertTrue($rule->isEnemyEligible($this->makeEnemy('245339-11')));
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
     * @param array<int, string> $uniqueKeys
     *
     * @return array<string, array<int, string>>
     */
    private static function uniqueKeyProvider(array $uniqueKeys): array
    {
        return array_combine($uniqueKeys, array_map(static fn(string $uniqueKey) => [$uniqueKey], $uniqueKeys));
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

    private function makeEnemy(string $uniqueKey): Enemy
    {
        [$npcId, $mdtId] = explode('-', $uniqueKey);

        $enemy                = new Enemy();
        $enemy->enemy_pack_id = null;
        $enemy->npc_id        = (int)$npcId;
        $enemy->mdt_id        = (int)$mdtId;

        return $enemy;
    }

    private function makeEnemyInPack(string $uniqueKey, int $group): Enemy
    {
        $enemy = $this->makeEnemy($uniqueKey);

        $enemyPack        = new EnemyPack();
        $enemyPack->id    = 1;
        $enemyPack->group = $group;

        $enemy->enemy_pack_id = $enemyPack->id;
        $enemy->setRelation('enemyPack', $enemyPack);

        return $enemy;
    }
}
