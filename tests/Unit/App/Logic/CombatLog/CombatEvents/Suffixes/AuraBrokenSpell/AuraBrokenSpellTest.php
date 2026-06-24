<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\AuraBrokenSpell;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBrokenSpell;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class AuraBrokenSpellTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraBrokenSpell')]
    #[DataProvider('parseEvent_givenAuraBrokenSpellEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenAuraBrokenSpellEvent_returnsCorrectValues(
        string $auraBrokenSpellEvent,
        int    $expectedExtraSpellId,
        string $expectedExtraSpellName,
        int    $expectedExtraSchool,
        string $expectedAuraType,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($auraBrokenSpellEvent);

        // Act
        /** @var CombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(CombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(Spell::class, $result->getPrefix());
        /** @var AuraBrokenSpell $suffix */
        $suffix = $result->getSuffix();
        Assert::assertInstanceOf(AuraBrokenSpell::class, $suffix);
        Assert::assertEquals($expectedExtraSpellId, $suffix->getExtraSpellId());
        Assert::assertEquals($expectedExtraSpellName, $suffix->getExtraSpellName());
        Assert::assertEquals($expectedExtraSchool, $suffix->getExtraSchool());
        Assert::assertEquals($expectedAuraType, $suffix->getAuraType());
    }

    /**
     * @return array<string, list<int|string>>
     */
    public static function parseEvent_givenAuraBrokenSpellEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'blinding-light-breaks-debuff' => [
                '3/25/2026 10:38:49.5041  SPELL_AURA_BROKEN_SPELL,Player-3674-0B71E4D2,"Whisker-TwistingNether-EU",0x512,0x80000000,Creature-0-4241-2526-8814-197406-0000C3ACE6,"Aggravated Skitterfly",0xa48,0x80000000,105421,"Blinding Light",0x2,153640,"Arcane Orb",64,DEBUFF',
                153640,
                'Arcane Orb',
                64,
                'DEBUFF',
            ],
            'blinding-light-breaks-second-debuff' => [
                '3/25/2026 10:38:49.5251  SPELL_AURA_BROKEN_SPELL,Player-1084-0B4087DE,"Panglong-TarrenMill-EU",0x511,0x80000000,Creature-0-4241-2526-8814-197398-000343ACE6,"Hungry Lasher",0xa48,0x80000000,105421,"Blinding Light",0x2,450617,"Flurry Strike",1,DEBUFF',
                450617,
                'Flurry Strike',
                1,
                'DEBUFF',
            ],
        ];
    }
}
