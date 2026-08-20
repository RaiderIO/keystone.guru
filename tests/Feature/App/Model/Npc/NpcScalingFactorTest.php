<?php

namespace Tests\Feature\App\Model\Npc;

use App\Models\Affix;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcHealth;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Pins Npc::getScalingFactor() to what real combat logs show (#4094): Murder Row, one Raider.IO run per key level,
 * observed max HP divided by the seeded (MDT) base health of the same NPC - Demon Fly (235257, trash) and Xathuux the
 * Annihilator (234647, boss); +11 from The Blinding Vale run 757063 (Radiant Spellsower 7,075,486 / 2,918,930 and
 * Ziekket 57,324,529 / 23,648,732). Every expected value below is such a measurement, not a number derived from the
 * formula.
 */
#[Group('Npc')]
#[Group('NpcScalingFactor')]
final class NpcScalingFactorTest extends PublicTestCase
{
    /** @param array<int, string> $affixes */
    #[Test]
    #[DataProvider('getScalingFactor_givenKeyLevel_matchesMeasuredTrashHealth_DataProvider')]
    public function getScalingFactor_givenKeyLevel_matchesMeasuredTrashHealth(int $keyLevel, array $affixes, float $measuredFactor): void
    {
        // Arrange
        $npc = $this->npc(NpcClassification::NPC_CLASSIFICATION_NORMAL);

        // Act
        $factor = $npc->getScalingFactor($keyLevel, $affixes);

        // Assert
        $this->assertEqualsWithDelta($measuredFactor, $factor, 0.0001);
    }

    /**
     * Demon Fly: seeded base 1,621,628; observed max HP 1,648,385 (+2), 1,894,872 (+4), 2,018,116 (+5), 2,270,279 (+6),
     * 2,918,930 (+7), 3,132,985 (+8), 3,347,040 (+9), 3,580,555 (+10), 4,320,017 (+12). The +7..+9 runs were in a
     * Fortified week - in a Tyrannical week trash stays on the bare curve there and bosses get the 1.25 instead.
     *
     * @return array<string, array{int, array<int, string>, float}>
     */
    public static function getScalingFactor_givenKeyLevel_matchesMeasuredTrashHealth_DataProvider(): array
    {
        return [
            '+2 (1.07 * 0.95 low key)'                 => [2, [], 1.0165],
            '+4 (round2(1.07^3) * 0.95)'               => [4, [], 1.1685],
            '+5 (round2(1.07^4) * 0.95)'               => [5, [], 1.2445],
            '+6 (round2(1.07^5), no modifiers)'        => [6, [], 1.40],
            '+7 fortified week (round2(1.07^6) * 1.2)' => [7, [Affix::AFFIX_FORTIFIED], 1.80],
            '+8 fortified week'                        => [8, [Affix::AFFIX_FORTIFIED], 1.932],
            '+9 fortified week'                        => [9, [Affix::AFFIX_FORTIFIED], 2.064],
            '+10 (both affixes regardless)'            => [10, [], 2.208],
            '+11 (round2(1.07^9 * 1.1) * 1.2)'         => [11, [], 2.424],
            '+12 (round2(1.07^9 * 1.1^2) * 1.2)'       => [12, [], 2.664],
        ];
    }

    /** @param array<int, string> $affixes */
    #[Test]
    #[DataProvider('getScalingFactor_givenKeyLevel_matchesMeasuredBossHealth_DataProvider')]
    public function getScalingFactor_givenKeyLevel_matchesMeasuredBossHealth(int $keyLevel, array $affixes, float $measuredFactor): void
    {
        // Arrange
        $npc = $this->npc(NpcClassification::NPC_CLASSIFICATION_BOSS);

        // Act
        $factor = $npc->getScalingFactor($keyLevel, $affixes);

        // Assert
        $this->assertEqualsWithDelta($measuredFactor, $factor, 0.0001);
    }

    /**
     * Xathuux the Annihilator: seeded base 23,648,733; observed max HP 24,291,978 (+2), 27,924,426 (+4), 29,740,647 (+5),
     * 31,783,898 (+6), 34,054,178 (+7), 36,551,484 (+8), 39,048,788 (+9), 52,216,404 (+10), 63,000,225 (+12). The +7..+9
     * runs were in a Fortified week, which leaves bosses on the bare curve.
     *
     * @return array<string, array{int, array<int, string>, float}>
     */
    public static function getScalingFactor_givenKeyLevel_matchesMeasuredBossHealth_DataProvider(): array
    {
        return [
            '+2 (1.07 * 0.96 MDT base correction)'       => [2, [], 1.0272],
            '+4'                                         => [4, [], 1.1808],
            '+5'                                         => [5, [], 1.2576],
            '+6'                                         => [6, [], 1.344],
            '+7 fortified week (no tyrannical)'          => [7, [Affix::AFFIX_FORTIFIED], 1.44],
            '+8 fortified week'                          => [8, [Affix::AFFIX_FORTIFIED], 1.5456],
            '+9 fortified week'                          => [9, [Affix::AFFIX_FORTIFIED], 1.6512],
            '+10 (round2(1.07^9) * 1.25 * 0.96)'         => [10, [], 2.208],
            '+11 (round2(1.07^9 * 1.1) * 1.25 * 0.96)'   => [11, [], 2.424],
            '+12 (round2(1.07^9 * 1.1^2) * 1.25 * 0.96)' => [12, [], 2.664],
        ];
    }

    #[Test]
    public function getScalingFactor_givenTyrannicalWeekBetween7And9_appliesTyrannicalToBossesOnly(): void
    {
        // Arrange
        $trash = $this->npc(NpcClassification::NPC_CLASSIFICATION_NORMAL);
        $boss  = $this->npc(NpcClassification::NPC_CLASSIFICATION_BOSS);

        // Act
        $trashFactor = $trash->getScalingFactor(8, [Affix::AFFIX_TYRANNICAL]);
        $bossFactor  = $boss->getScalingFactor(8, [Affix::AFFIX_TYRANNICAL]);

        // Assert - the other week's mirror image of the measured +8
        $this->assertEqualsWithDelta(1.61, $trashFactor, 0.0001);
        $this->assertEqualsWithDelta(1.61 * 1.25 * 0.96, $bossFactor, 0.0001);
    }

    #[Test]
    public function getScalingFactor_givenAffixBelow7_appliesNeither(): void
    {
        // Arrange - the week's affix group carries Fortified/Tyrannical, but they only kick in from +7
        $trash = $this->npc(NpcClassification::NPC_CLASSIFICATION_NORMAL);
        $boss  = $this->npc(NpcClassification::NPC_CLASSIFICATION_BOSS);

        // Act
        $trashFactor = $trash->getScalingFactor(6, [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TYRANNICAL]);
        $bossFactor  = $boss->getScalingFactor(6, [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TYRANNICAL]);

        // Assert
        $this->assertEqualsWithDelta(1.40, $trashFactor, 0.0001);
        $this->assertEqualsWithDelta(1.40 * 0.96, $bossFactor, 0.0001);
    }

    #[Test]
    public function getScalingFactor_givenNoAffixesBetween7And9_appliesNeither(): void
    {
        // Arrange - without the week's affixes the factor cannot know which one is active, so it assumes none
        $trash = $this->npc(NpcClassification::NPC_CLASSIFICATION_NORMAL);
        $boss  = $this->npc(NpcClassification::NPC_CLASSIFICATION_BOSS);

        // Act + Assert
        $this->assertEqualsWithDelta(1.50, $trash->getScalingFactor(7), 0.0001);
        $this->assertEqualsWithDelta(1.50 * 0.96, $boss->getScalingFactor(7), 0.0001);
    }

    #[Test]
    public function getScalingFactor_givenAffixAboveThreshold_doesNotApplyItTwice(): void
    {
        // Arrange
        $trash = $this->npc(NpcClassification::NPC_CLASSIFICATION_NORMAL);

        // Act
        $factor = $trash->getScalingFactor(10, [Affix::AFFIX_FORTIFIED, Affix::AFFIX_TYRANNICAL]);

        // Assert
        $this->assertEqualsWithDelta(2.208, $factor, 0.0001);
    }

    #[Test]
    public function calculateHealthForKey_givenMeasuredBase_reproducesObservedMaxHp(): void
    {
        // Arrange - Demon Fly's seeded base and its observed +8 max HP (Fortified week)
        $gameVersion = GameVersion::getDefaultGameVersion();
        $npc         = $this->npc(NpcClassification::NPC_CLASSIFICATION_NORMAL);
        $npc->setRelation('npcHealths', new Collection([
            new NpcHealth(['npc_id' => $npc->id, 'game_version_id' => $gameVersion->id, 'health' => 1_621_628, 'percentage' => null]),
        ]));

        // Act
        $health = $npc->calculateHealthForKey($gameVersion, 8, [Affix::AFFIX_FORTIFIED]);

        // Assert
        $this->assertEqualsWithDelta(3_132_985, $health, 1);
    }

    private function npc(string $classification): Npc
    {
        return new Npc([
            'id'                => 1,
            'classification_id' => NpcClassification::ALL[$classification],
        ]);
    }
}
