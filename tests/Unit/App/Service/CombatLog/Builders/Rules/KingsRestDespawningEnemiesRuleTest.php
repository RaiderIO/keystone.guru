<?php

namespace Tests\Unit\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLogging;
use App\Service\CombatLog\Builders\Rules\KingsRestDespawningEnemiesRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('KingsRestDespawningEnemiesRule')]
class KingsRestDespawningEnemiesRuleTest extends PublicTestCase
{
    private const NPC_ID_THUNDERING_TOTEM = 135761;

    private const NPC_ID_EXPLOSIVE_TOTEM = 135764;

    private const NPC_ID_TORRENT_TOTEM = 135765;

    private const NPC_ID_AKAALI_THE_CONQUEROR = 269808;

    private const NPC_ID_ZANAZAL_THE_WISE = 269810;

    private const NPC_ID_KULA_THE_BUTCHER = 269811;

    private const NPC_ID_MINION_OF_ZUL = 138493;

    private const NPC_ID_MINION_OF_ZUL_EARLY_DUNGEON = 133943;

    private const NPC_ID_SHADOW_OF_ZUL = 138489;

    private const NPC_ID_REBAN = 136984;

    private const NPC_ID_TZALA = 136976;

    private const NPC_ID_KING_DAZAR = 136160;

    #[Test]
    public function appliesToDungeon_givenKingsRest_returnsTrue(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::KINGS_REST->value));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function appliesToDungeon_givenAnotherDungeon_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->appliesToDungeon($this->makeDungeon(DungeonKey::THE_BLINDING_VALE->value));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('councilOfTribesTotemProvider')]
    public function onEnemyDied_givenACouncilOfTribesTotemDied_awardsTheThreeCouncilBosses(int $totemNpcId): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied($totemNpcId, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            self::NPC_ID_AKAALI_THE_CONQUEROR,
            self::NPC_ID_ZANAZAL_THE_WISE,
            self::NPC_ID_KULA_THE_BUTCHER,
        ], $result);
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function councilOfTribesTotemProvider(): array
    {
        return [
            'Thundering Totem' => [self::NPC_ID_THUNDERING_TOTEM],
            'Explosive Totem'  => [self::NPC_ID_EXPLOSIVE_TOTEM],
            'Torrent Totem'    => [self::NPC_ID_TORRENT_TOTEM],
        ];
    }

    /**
     * All three totems are usually killed, each in its own pull - the bosses must be awarded to the first one only.
     */
    #[Test]
    public function onEnemyDied_givenASecondCouncilOfTribesTotemDied_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_EXPLOSIVE_TOTEM, null);

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_THUNDERING_TOTEM, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * If the Council does reach us after all, awarding it a second time would attach a duplicate pull to the route.
     */
    #[Test]
    public function onEnemyDied_givenTheCouncilOfTribesDiedBeforeItsTotems_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_AKAALI_THE_CONQUEROR, null);
        $rule->onEnemyDied(self::NPC_ID_ZANAZAL_THE_WISE, null);
        $rule->onEnemyDied(self::NPC_ID_KULA_THE_BUTCHER, null);

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_EXPLOSIVE_TOTEM, null);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function onEnemyDied_givenAMinionOfZulDiedAfterTheCouncilOfTribes_awardsTheShadowOfZul(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_EXPLOSIVE_TOTEM, null);

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_MINION_OF_ZUL, null);

        // Assert
        $this->assertEquals([self::NPC_ID_SHADOW_OF_ZUL], $result);
    }

    /**
     * The Shadow of Zul sits well past the Council - a Minion of Zul dying before it is not the party reaching it.
     */
    #[Test]
    public function onEnemyDied_givenAMinionOfZulDiedBeforeTheCouncilOfTribes_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_MINION_OF_ZUL, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * 133943 carries the name "Minion of Zul" as well, but is mapped in the early dungeon packs rather than in the
     * Shadow of Zul's own pack - killing it must not award anything.
     */
    #[Test]
    public function onEnemyDied_givenTheEarlyDungeonMinionOfZulDiedAfterTheCouncilOfTribes_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_EXPLOSIVE_TOTEM, null);

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_MINION_OF_ZUL_EARLY_DUNGEON, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * The Shadow of Zul cannot be skipped on the way to King Dazar, so Reban's death is the last chance to account
     * for it on a run where no Minion of Zul ever reached us.
     */
    #[Test]
    public function onEnemyDied_givenRebanDiedWithTheShadowOfZulUnaccountedFor_awardsItAlongsideTheFinalBosses(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_REBAN, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            self::NPC_ID_SHADOW_OF_ZUL,
            self::NPC_ID_TZALA,
            self::NPC_ID_KING_DAZAR,
        ], $result);
    }

    #[Test]
    public function onEnemyDied_givenRebanDiedAfterTheShadowOfZulWasAwarded_awardsOnlyTheFinalBosses(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_EXPLOSIVE_TOTEM, null);
        $rule->onEnemyDied(self::NPC_ID_MINION_OF_ZUL, null);

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_REBAN, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            self::NPC_ID_TZALA,
            self::NPC_ID_KING_DAZAR,
        ], $result);
    }

    #[Test]
    public function onEnemyDied_givenRebanDiedTwice_awardsNothingTheSecondTime(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_REBAN, null);

        // Act
        $result = $rule->onEnemyDied(self::NPC_ID_REBAN, null);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function onEnemyDied_givenAnUnrelatedNpcDied_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied(135322, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * The rule only ever awards, it must never take an enemy out of the running for a normal spatial match.
     */
    #[Test]
    public function hasActiveFirstPassExclusion_givenEveryAwardFired_returnsFalse(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(self::NPC_ID_EXPLOSIVE_TOTEM, null);
        $rule->onEnemyDied(self::NPC_ID_MINION_OF_ZUL, null);
        $rule->onEnemyDied(self::NPC_ID_REBAN, null);

        // Act
        $result = $rule->hasActiveFirstPassExclusion();

        // Assert
        $this->assertFalse($result);
    }

    private function makeRule(): KingsRestDespawningEnemiesRule
    {
        return new KingsRestDespawningEnemiesRule(new DungeonRouteBuilderLogging());
    }

    private function makeDungeon(string $key): Dungeon
    {
        $dungeon      = new Dungeon();
        $dungeon->key = $key;

        return $dungeon;
    }
}
