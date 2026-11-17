<?php

namespace Tests\Unit\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Rules\DenOfNalorakkDefeatedEncounterRule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('DenOfNalorakkDefeatedEncounterRule')]
class DenOfNalorakkDefeatedEncounterRuleTest extends PublicTestCase
{
    #[Test]
    public function appliesToDungeon_givenDenOfNalorakk_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::DEN_OF_NALORAKK->value));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function appliesToDungeon_givenAnotherDungeon_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::ALTAR_OF_FANGS->value));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function onEnemyDied_givenNalorakkDied_awardsZuljarraAndTheEchoOfNalorakk(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied(NpcId::NALORAKK->value, null);

        // Assert
        $this->assertEquals([NpcId::ZULJARRA->value, NpcId::ECHO_OF_NALORAKK->value], $result);
    }

    /**
     * If Zul'jarra's death does reach us, awarding her again off Nalorakk would duplicate her in the route.
     */
    #[Test]
    public function onEnemyDied_givenZuljarraDiedBeforeNalorakk_awardsOnlyTheEchoOfNalorakk(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::ZULJARRA->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::NALORAKK->value, null);

        // Assert
        $this->assertEquals([NpcId::ECHO_OF_NALORAKK->value], $result);
    }

    #[Test]
    public function onEnemyDied_givenNalorakkDiedTwice_awardsNothingTheSecondTime(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::NALORAKK->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::NALORAKK->value, null);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function onEnemyDied_givenAnUnrelatedNpcDied_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied(NpcId::ECHO_OF_NALORAKK->value, null);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function onRunFinished_givenNalorakkNeverDied_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onRunFinished();

        // Assert
        $this->assertEmpty($result);
    }

    private function makeRule(): DenOfNalorakkDefeatedEncounterRule
    {
        return new DenOfNalorakkDefeatedEncounterRule(new DungeonRouteBuilderLogging());
    }

    private function makeDungeon(string $key): Dungeon
    {
        $dungeon      = new Dungeon();
        $dungeon->key = $key;

        return $dungeon;
    }
}
