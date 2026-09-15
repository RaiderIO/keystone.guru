<?php

namespace Tests\Unit\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Rules\BossKillFloorCutoffRule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('BossKillFloorCutoffRule')]
class BossKillFloorCutoffRuleTest extends PublicTestCase
{
    #[Test]
    public function appliesToDungeon_givenVoidscarArena_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::VOIDSCAR_ARENA->value));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function appliesToDungeon_givenAnUnlistedDungeon_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::THE_BLINDING_VALE->value));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEnemyEligibleOnFirstPass_givenABossDiedOnALaterFloor_returnsFalseForEarlierFloors(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(1, $this->makeEnemy(floorIndex: 2, isBoss: true));

        // Assert
        $this->assertSame(2, $rule->getMinimumFloorIndex());
        $this->assertFalse($rule->isEnemyEligibleOnFirstPass($this->makeEnemy(floorIndex: 1)));
        $this->assertTrue($rule->isEnemyEligibleOnFirstPass($this->makeEnemy(floorIndex: 2)));
        $this->assertTrue($rule->isEnemyEligibleOnFirstPass($this->makeEnemy(floorIndex: 3)));
    }

    /**
     * The exclusion is soft - the builder retries without it rather than dropping the enemy's forces entirely.
     */
    #[Test]
    public function isEnemyEligible_givenABossDiedOnALaterFloor_returnsTrueForEarlierFloors(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(1, $this->makeEnemy(floorIndex: 2, isBoss: true));

        // Assert
        $this->assertTrue($rule->isEnemyEligible($this->makeEnemy(floorIndex: 1)));
        $this->assertTrue($rule->hasActiveFirstPassExclusion());
    }

    #[Test]
    public function onEnemyDied_givenANonBossEnemy_doesNotRaiseTheMinimumFloorIndex(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(1, $this->makeEnemy(floorIndex: 2, isBoss: false));

        // Assert
        $this->assertSame(0, $rule->getMinimumFloorIndex());
        $this->assertFalse($rule->hasActiveFirstPassExclusion());
    }

    /**
     * A facade floor has no meaningful index of its own, so it must never become the cutoff.
     */
    #[Test]
    public function onEnemyDied_givenABossOnAFacadeFloor_doesNotRaiseTheMinimumFloorIndex(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(1, $this->makeEnemy(floorIndex: 2, isBoss: true, facade: true));

        // Assert
        $this->assertSame(0, $rule->getMinimumFloorIndex());
    }

    #[Test]
    public function onEnemyDied_givenABossThatDidNotResolveToAnEnemy_doesNotRaiseTheMinimumFloorIndex(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $rule->onEnemyDied(1, null);

        // Assert
        $this->assertSame(0, $rule->getMinimumFloorIndex());
    }

    /**
     * Bosses are not killed in floor order in every run, so a boss on an earlier floor must not lower the cutoff.
     */
    #[Test]
    public function onEnemyDied_givenABossOnAnEarlierFloorThanTheCutoff_doesNotLowerTheMinimumFloorIndex(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(1, $this->makeEnemy(floorIndex: 3, isBoss: true));

        // Act
        $rule->onEnemyDied(2, $this->makeEnemy(floorIndex: 2, isBoss: true));

        // Assert
        $this->assertSame(3, $rule->getMinimumFloorIndex());
    }

    private function makeRule(): BossKillFloorCutoffRule
    {
        return new BossKillFloorCutoffRule(new DungeonRouteBuilderLogging());
    }

    private function makeDungeon(string $key): Dungeon
    {
        $dungeon      = new Dungeon();
        $dungeon->key = $key;

        return $dungeon;
    }

    private function makeEnemy(int $floorIndex, bool $isBoss = false, bool $facade = false): Enemy
    {
        $floor         = new Floor();
        $floor->id     = $floorIndex;
        $floor->index  = $floorIndex;
        $floor->facade = $facade;

        $npc                    = new Npc();
        $npc->classification_id = NpcClassification::ALL[$isBoss
            ? NpcClassification::NPC_CLASSIFICATION_BOSS
            : NpcClassification::NPC_CLASSIFICATION_NORMAL];

        $enemy           = new Enemy();
        $enemy->id       = $floorIndex;
        $enemy->npc_id   = 1;
        $enemy->floor_id = $floor->id;
        $enemy->setRelation('floor', $floor);
        $enemy->setRelation('npc', $npc);

        return $enemy;
    }
}
