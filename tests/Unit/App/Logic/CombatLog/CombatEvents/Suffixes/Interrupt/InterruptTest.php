<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class InterruptTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Interrupt')]
    #[DataProvider('parseEvent_givenInterruptEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenInterruptEvent_returnsCorrectValues(
        string $interruptEvent,
        int    $expectedExtraSpellId,
        string $expectedExtraSpellName,
        int    $expectedExtraSchool,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($interruptEvent);

        // Act
        /** @var CombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(CombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(Spell::class, $result->getPrefix());
        /** @var Interrupt $suffix */
        $suffix = $result->getSuffix();
        Assert::assertInstanceOf(Interrupt::class, $suffix);
        Assert::assertEquals($expectedExtraSpellId, $suffix->getExtraSpellId());
        Assert::assertEquals($expectedExtraSpellName, $suffix->getExtraSpellName());
        Assert::assertEquals($expectedExtraSchool, $suffix->getExtraSchool());
    }

    public static function parseEvent_givenInterruptEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'rebuke-interrupts-surge' => [
                '3/25/2026 10:47:23.2961  SPELL_INTERRUPT,Player-580-0AE12FF4,"Palatsch-Blackmoore-EU",0x512,0x80000020,Creature-0-4241-2526-8814-196045-000343ACE6,"Corrupted Manafiend",0xa48,0x80000000,96231,"Rebuke",0x1,388862,"Surge",64',
                388862,
                'Surge',
                64,
            ],
            'rebuke-second-interrupt' => [
                '3/25/2026 10:47:24.9421  SPELL_INTERRUPT,Player-3674-0AFCC88F,"Legain-TwistingNether-EU",0x512,0x80000000,Creature-0-4241-2526-8814-196045-0003C3ACE6,"Corrupted Manafiend",0x10a48,0x80000000,96231,"Rebuke",0x1,388862,"Surge",64',
                388862,
                'Surge',
                64,
            ],
        ];
    }
}
