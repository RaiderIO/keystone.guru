<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\CastFailed;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastFailed;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class CastFailedTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('CastFailed')]
    #[DataProvider('parseEvent_givenCastFailedEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenCastFailedEvent_returnsCorrectValues(
        string $castFailedEvent,
        string $expectedFailedType,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($castFailedEvent);

        // Act
        /** @var CombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(CombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(Spell::class, $result->getPrefix());
        /** @var CastFailed $suffix */
        $suffix = $result->getSuffix();
        Assert::assertInstanceOf(CastFailed::class, $suffix);
        Assert::assertEquals($expectedFailedType, $suffix->getFailedType());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function parseEvent_givenCastFailedEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'item-not-ready' => [
                '3/25/2026 10:38:35.5361  SPELL_CAST_FAILED,Player-1084-0B4087DE,"Panglong-TarrenMill-EU",0x511,0x80000000,0000000000000000,nil,0x80000000,0x80000000,383781,"Algeth\'ar Puzzle",0x1,"Item is not ready yet"',
                'Item is not ready yet',
            ],
            'not-yet-recovered' => [
                '3/25/2026 10:38:42.2041  SPELL_CAST_FAILED,Player-1084-0B4087DE,"Panglong-TarrenMill-EU",0x511,0x80000000,0000000000000000,nil,0x80000000,0x80000000,107428,"Rising Sun Kick",0x1,"Not yet recovered"',
                'Not yet recovered',
            ],
        ];
    }
}
