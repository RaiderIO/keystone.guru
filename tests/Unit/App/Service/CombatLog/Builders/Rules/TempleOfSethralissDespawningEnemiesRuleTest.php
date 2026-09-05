<?php

namespace Tests\Unit\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Rules\TempleOfSethralissDespawningEnemiesRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('TempleOfSethralissDespawningEnemiesRule')]
class TempleOfSethralissDespawningEnemiesRuleTest extends PublicTestCase
{
    #[Test]
    public function appliesToDungeon_givenTempleOfSethraliss_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::TEMPLE_OF_SETHRALISS->value));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function appliesToDungeon_givenAnotherDungeon_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::KINGS_REST->value));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('galvazztNpcIdProvider')]
    public function onEnemyDied_givenGalvazztDied_awardsTheStaticAnomalies(int $galvazztNpcId): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied($galvazztNpcId, null);

        // Assert
        $this->assertEquals([NpcId::STATIC_ANOMALY->value], $result);
    }

    /**
     * Galvazzt is mapped under two npc_ids across the mapping versions we still build routes for, and either death
     * means the anomalies are gone.
     *
     * @return array<string, array{int}>
     */
    public static function galvazztNpcIdProvider(): array
    {
        return [
            'Galvazzt'          => [NpcId::GALVAZZT->value],
            'Galvazzt restored' => [NpcId::GALVAZZT_RESTORED->value],
        ];
    }

    /**
     * A run that sends us both of Galvazzt's npc_ids must not attach the anomalies to the route twice.
     */
    #[Test]
    public function onEnemyDied_givenBothGalvazztNpcIdsDied_awardsTheStaticAnomaliesOnce(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::GALVAZZT->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::GALVAZZT_RESTORED->value, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * If an anomaly's death does reach us after all, awarding the npc afterwards would duplicate it in the route.
     */
    #[Test]
    public function onEnemyDied_givenAStaticAnomalyDiedBeforeGalvazzt_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::STATIC_ANOMALY->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::GALVAZZT->value, null);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function onEnemyDied_givenAnUnrelatedNpcDied_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied(133384, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * The rule only ever awards, it must never take an enemy out of the running for a normal spatial match.
     */
    #[Test]
    public function hasActiveFirstPassExclusion_givenTheAwardFired_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::GALVAZZT_RESTORED->value, null);

        // Act
        $result = $rule->hasActiveFirstPassExclusion();

        // Assert
        $this->assertFalse($result);
    }

    private function makeRule(): TempleOfSethralissDespawningEnemiesRule
    {
        return new TempleOfSethralissDespawningEnemiesRule(new DungeonRouteBuilderLogging());
    }

    private function makeDungeon(string $key): Dungeon
    {
        $dungeon      = new Dungeon();
        $dungeon->key = $key;

        return $dungeon;
    }
}
