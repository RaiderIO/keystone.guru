<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\Heal;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Heal;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class HealTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Heal')]
    #[DataProvider('parseEvent_givenSpellHealEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenSpellHealEvent_returnsCorrectValues(
        string $spellHealEvent,
        int    $expectedAmount,
        int    $expectedOverHealing,
        int    $expectedAbsorbed,
        bool   $expectedIsCritical,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($spellHealEvent);

        // Act
        /** @var AdvancedCombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(Spell::class, $result->getPrefix());
        /** @var Heal $suffix */
        $suffix = $result->getSuffix();
        Assert::assertInstanceOf(Heal::class, $suffix);
        Assert::assertEquals($expectedAmount, $suffix->getAmount());
        Assert::assertEquals($expectedOverHealing, $suffix->getOverHealing());
        Assert::assertEquals($expectedAbsorbed, $suffix->getAbsorbed());
        Assert::assertEquals($expectedIsCritical, $suffix->isCritical());
    }

    public static function parseEvent_givenSpellHealEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'riptide-full-overheal' => [
                '3/25/2026 10:38:29.4491  SPELL_HEAL,Player-1303-09231FEC,"Riptidewave-Aggra(Português)-EU",0x512,0x80000000,Player-580-0AE12FF4,"Palatsch-Blackmoore-EU",0x512,0x80000020,61295,"Riptide",0x8,Player-580-0AE12FF4,0000000000000000,291718,446020,2494,698,4052,414,0,0,0,250000,250000,0,1801.07,-3077.86,2097,5.2480,247,19428,19428,0,0,nil',
                19428,
                19428,
                0,
                false,
            ],
            'earth-shield-critical' => [
                '3/25/2026 10:38:29.5101  SPELL_HEAL,Player-1303-09231FEC,"Riptidewave-Aggra(Português)-EU",0x512,0x80000000,Player-1303-09231FEC,"Riptidewave-Aggra(Português)-EU",0x512,0x80000000,379,"Earth Shield",0x8,Player-1303-09231FEC,0000000000000000,350281,355540,770,1973,1834,996,0,0,0,271070,275000,0,1776.73,-3060.78,2097,6.2619,252,17716,17716,0,1,1',
                17716,
                17716,
                0,
                true,
            ],
        ];
    }
}
