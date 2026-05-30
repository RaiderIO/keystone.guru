<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\ExtraAttacks;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\ExtraAttacks;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class ExtraAttacksTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('ExtraAttacks')]
    #[DataProvider('parseEvent_givenExtraAttacksEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenExtraAttacksEvent_returnsCorrectValues(
        string $extraAttacksEvent,
        int    $expectedAmount,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($extraAttacksEvent);

        // Act
        /** @var CombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(CombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(Spell::class, $result->getPrefix());
        /** @var ExtraAttacks $suffix */
        $suffix = $result->getSuffix();
        Assert::assertInstanceOf(ExtraAttacks::class, $suffix);
        Assert::assertEquals($expectedAmount, $suffix->getAmount());
    }

    public static function parseEvent_givenExtraAttacksEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'skyfury-one-attack' => [
                '3/25/2026 10:38:38.5361  SPELL_EXTRA_ATTACKS,Player-580-0AE12FF4,"Palatsch-Blackmoore-EU",0x512,0x80000020,Player-580-0AE12FF4,"Palatsch-Blackmoore-EU",0x512,0x80000020,465660,"Skyfury",0x1,1',
                1,
            ],
            'skyfury-second-player' => [
                '3/25/2026 10:38:39.1361  SPELL_EXTRA_ATTACKS,Player-3674-0AFCC88F,"Legain-TwistingNether-EU",0x512,0x80000000,Player-3674-0AFCC88F,"Legain-TwistingNether-EU",0x512,0x80000000,465660,"Skyfury",0x1,1',
                1,
            ],
        ];
    }
}
