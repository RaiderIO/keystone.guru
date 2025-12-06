<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\CombatantInfo;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V9SoD\CombatantInfoV9SoD;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class CombatantInfoV9SoDTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('CombatantInfo')]
    #[DataProvider('parseEvent_GivenCombatantInfoEvent_ShouldValidateBasicStats_DataProvider')]
    public function parseEvent_GivenCombatantInfoEvent_ShouldValidateBasicStats(
        string $combatantInfoEvent,
        string $expectedPlayerGuid,
        int    $expectedFaction,
        int    $expectedStrength,
        int    $expectedAgility,
        int    $expectedStamina,
        int    $expectedIntellect,
        int    $expectedSpirit,
        int    $expectedDodge,
        int    $expectedParry,
        int    $expectedBlock,
        int    $expectedCritMelee,
        int    $expectedCritRanged,
        int    $expectedCritSpell,
        int    $expectedSpeed,
        int    $expectedLifesteal,
        int    $expectedHasteMelee,
        int    $expectedHasteRanged,
        int    $expectedHasteSpell,
        int    $expectedAvoidance,
        int    $expectedMastery,
        int    $expectedVersatilityDamageDone,
        int    $expectedVersatilityHealingDone,
        int    $expectedVersatilityDamageTaken,
        int    $expectedArmor,
        int    $expectedCurrentSpecId,
        int    $expectedHonorLevel,
        int    $expectedSeason,
        int    $expectedRating,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($combatantInfoEvent);

        // Act
        /** @var CombatantInfoV9SoD $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::CLASSIC_SOD_1_15_5);

        // Assert
        Assert::assertInstanceOf(CombatantInfoV9SoD::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedPlayerGuid, $parseEventResult->getPlayerGuid());
        Assert::assertEquals($expectedFaction, $parseEventResult->getFaction());
        Assert::assertEquals($expectedStrength, $parseEventResult->getStrength());
        Assert::assertEquals($expectedAgility, $parseEventResult->getAgility());
        Assert::assertEquals($expectedStamina, $parseEventResult->getStamina());
        Assert::assertEquals($expectedIntellect, $parseEventResult->getIntellect());
        Assert::assertEquals($expectedSpirit, $parseEventResult->getSpirit());
        Assert::assertEquals($expectedDodge, $parseEventResult->getDodge());
        Assert::assertEquals($expectedParry, $parseEventResult->getParry());
        Assert::assertEquals($expectedBlock, $parseEventResult->getBlock());
        Assert::assertEquals($expectedCritMelee, $parseEventResult->getCritMelee());
        Assert::assertEquals($expectedCritRanged, $parseEventResult->getCritRanged());
        Assert::assertEquals($expectedCritSpell, $parseEventResult->getCritSpell());
        Assert::assertEquals($expectedSpeed, $parseEventResult->getSpeed());
        Assert::assertEquals($expectedLifesteal, $parseEventResult->getLifesteal());
        Assert::assertEquals($expectedHasteMelee, $parseEventResult->getHasteMelee());
        Assert::assertEquals($expectedHasteRanged, $parseEventResult->getHasteRanged());
        Assert::assertEquals($expectedHasteSpell, $parseEventResult->getHasteSpell());
        Assert::assertEquals($expectedAvoidance, $parseEventResult->getAvoidance());
        Assert::assertEquals($expectedMastery, $parseEventResult->getMastery());
        Assert::assertEquals($expectedVersatilityDamageDone, $parseEventResult->getVersatilityDamageDone());
        Assert::assertEquals($expectedVersatilityHealingDone, $parseEventResult->getVersatilityHealingDone());
        Assert::assertEquals($expectedVersatilityDamageTaken, $parseEventResult->getVersatilityDamageTaken());
        Assert::assertEquals($expectedArmor, $parseEventResult->getArmor());
        Assert::assertEquals($expectedCurrentSpecId, $parseEventResult->getCurrentSpecId());
        Assert::assertEquals($expectedHonorLevel, $parseEventResult->getHonorLevel());
        Assert::assertEquals($expectedSeason, $parseEventResult->getSeason());
        Assert::assertEquals($expectedRating, $parseEventResult->getRating());
    }

    public static function parseEvent_GivenCombatantInfoEvent_ShouldValidateBasicStats_DataProvider(): array
    {
        return [
            [
                '12/9/2024 19:19:15.2340  COMBATANT_INFO,Player-5827-02693AFC,1,198,197,517,450,320,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1492,0,(18,0,33),(),[(231167,76,(0,6933,0),(),()),(228247,66,(),(),()),(231170,76,(),(),()),(53,1,(),(),()),(231169,76,(0,7024,0),(),()),(231171,76,(0,7110,0),(),()),(231168,76,(0,6744,0),(),()),(231165,76,(0,7541,0),(),()),(226579,66,(0,7112,0),(),()),(231166,76,(0,6741,0),(),()),(228243,71,(0,7518,0),(),()),(230846,74,(0,7519,0),(),()),(231785,77,(),(),()),(230269,75,(),(),()),(17078,72,(7564,6749,0),(),()),(230838,81,(2504,2627,0),(),()),(220598,53,(),(),()),(13004,58,(),(),()),(0,0,(),(),())],[Player-5827-02693AFC,425198,1,Player-5827-02693AFC,413251,1,Player-5827-02693AFC,1213254,1,Player-5827-02693AFC,473387,1,Player-5827-01FF533F,425600,1,Player-5827-02693AFC,473450,1,Player-5827-02693AFC,473403,1,Player-5827-02693AFC,473441,1,Player-5827-02693AFC,473476,1,Player-5827-02693AFC,473399,1],0,0,()',
                'Player-5827-02693AFC',
                1,
                198,
                197,
                517,
                450,
                320,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                1492,
                0,
                0,
                0,
                0,
            ],
        ];
    }
}
