<?php

namespace Tests\Unit\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
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

    /**
     * The first totem to die also awards its two siblings, so the whole Council of Tribes lands in one pull instead
     * of the other totems' real (later) deaths spawning a second one.
     */
    /**
     * @param array<int, int> $expectedSiblingTotemNpcIds
     */
    #[Test]
    #[DataProvider('councilOfTribesTotemProvider')]
    public function onEnemyDied_givenACouncilOfTribesTotemDied_awardsTheOtherTotemsAndTheThreeCouncilBosses(int $totemNpcId, array $expectedSiblingTotemNpcIds): void
    {
        // Arrange
        $rule = $this->makeRule();

        // Act
        $result = $rule->onEnemyDied($totemNpcId, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            ...$expectedSiblingTotemNpcIds,
            NpcId::AKAALI_THE_CONQUEROR->value,
            NpcId::ZANAZAL_THE_WISE->value,
            NpcId::KULA_THE_BUTCHER->value,
        ], $result);
    }

    /**
     * @return array<string, array{int, array<int, int>}>
     */
    public static function councilOfTribesTotemProvider(): array
    {
        return [
            'Thundering Totem' => [
                NpcId::THUNDERING_TOTEM->value,
                [NpcId::EXPLOSIVE_TOTEM->value, NpcId::TORRENT_TOTEM->value],
            ],
            'Explosive Totem' => [
                NpcId::EXPLOSIVE_TOTEM->value,
                [NpcId::THUNDERING_TOTEM->value, NpcId::TORRENT_TOTEM->value],
            ],
            'Torrent Totem' => [
                NpcId::TORRENT_TOTEM->value,
                [NpcId::THUNDERING_TOTEM->value, NpcId::EXPLOSIVE_TOTEM->value],
            ],
        ];
    }

    /**
     * The second and third totems' own (real) deaths must not re-award anything the first totem's death already did.
     */
    #[Test]
    public function onEnemyDied_givenASecondAndThirdCouncilOfTribesTotemDied_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::EXPLOSIVE_TOTEM->value, null);
        $rule->onEnemyDied(NpcId::THUNDERING_TOTEM->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::TORRENT_TOTEM->value, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * If the Council does reach us after all, awarding it a second time would attach a duplicate pull to the route -
     * but the other two totems still had not been accounted for, so they are awarded regardless of encounter order.
     */
    #[Test]
    public function onEnemyDied_givenTheCouncilOfTribesDiedBeforeItsTotems_awardsOnlyTheOtherTotems(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::AKAALI_THE_CONQUEROR->value, null);
        $rule->onEnemyDied(NpcId::ZANAZAL_THE_WISE->value, null);
        $rule->onEnemyDied(NpcId::KULA_THE_BUTCHER->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::EXPLOSIVE_TOTEM->value, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            NpcId::THUNDERING_TOTEM->value,
            NpcId::TORRENT_TOTEM->value,
        ], $result);
    }

    #[Test]
    public function onEnemyDied_givenAMinionOfZulDiedAfterTheCouncilOfTribes_awardsTheShadowOfZul(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::EXPLOSIVE_TOTEM->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::MINION_OF_ZUL->value, null);

        // Assert
        $this->assertEquals([NpcId::SHADOW_OF_ZUL->value], $result);
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
        $result = $rule->onEnemyDied(NpcId::MINION_OF_ZUL->value, null);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * NpcId::MINION_OF_ZUL_EARLY_DUNGEON carries the name "Minion of Zul" as well, but is mapped in the early dungeon
     * packs rather than in the Shadow of Zul's own pack - killing it must not award anything.
     */
    #[Test]
    public function onEnemyDied_givenTheEarlyDungeonMinionOfZulDiedAfterTheCouncilOfTribes_awardsNothing(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::EXPLOSIVE_TOTEM->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::MINION_OF_ZUL_EARLY_DUNGEON->value, null);

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
        $result = $rule->onEnemyDied(NpcId::REBAN->value, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            NpcId::SHADOW_OF_ZUL->value,
            NpcId::TZALA->value,
            NpcId::KING_DAZAR->value,
        ], $result);
    }

    #[Test]
    public function onEnemyDied_givenRebanDiedAfterTheShadowOfZulWasAwarded_awardsOnlyTheFinalBosses(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::EXPLOSIVE_TOTEM->value, null);
        $rule->onEnemyDied(NpcId::MINION_OF_ZUL->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::REBAN->value, null);

        // Assert
        $this->assertEqualsCanonicalizing([
            NpcId::TZALA->value,
            NpcId::KING_DAZAR->value,
        ], $result);
    }

    #[Test]
    public function onEnemyDied_givenRebanDiedTwice_awardsNothingTheSecondTime(): void
    {
        // Arrange
        $rule = $this->makeRule();
        $rule->onEnemyDied(NpcId::REBAN->value, null);

        // Act
        $result = $rule->onEnemyDied(NpcId::REBAN->value, null);

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
        $rule->onEnemyDied(NpcId::EXPLOSIVE_TOTEM->value, null);
        $rule->onEnemyDied(NpcId::MINION_OF_ZUL->value, null);
        $rule->onEnemyDied(NpcId::REBAN->value, null);

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
