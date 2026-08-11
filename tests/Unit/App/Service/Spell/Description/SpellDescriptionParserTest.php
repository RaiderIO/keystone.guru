<?php

namespace Tests\Unit\App\Service\Spell\Description;

use App\Service\Spell\Description\ArraySpellDescriptionContext;
use App\Service\Spell\Description\Dtos\SpellEffectData;
use App\Service\Spell\Description\SpellDescriptionContextInterface;
use App\Service\Spell\Description\SpellDescriptionParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('SpellDescription')]
final class SpellDescriptionParserTest extends TestCase
{
    /** The spell every template in this test belongs to. */
    private const int SPELL_ID = 1000;

    /** A second spell, for the cross-spell references descriptions are full of. */
    private const int OTHER_SPELL_ID = 2000;

    #[Test]
    #[DataProvider('templateProvider')]
    public function parse_givenTemplate_returnsRenderedDescription(string $template, string $expected): void
    {
        // Arrange
        $parser = new SpellDescriptionParser();

        // Act
        $result = $parser->parse($this->createContext(), self::SPELL_ID, $template)->render();

        // Assert
        $this->assertSame($expected, $result, $template);
    }

    /** @return array<string, array{string, string}> */
    public static function templateProvider(): array
    {
        return [
            'plain text is untouched' => [
                'Slams the ground.',
                'Slams the ground.',
            ],
            'effect points' => [
                'Inflicts $s1 Fire damage.',
                'Inflicts 25 Fire damage.',
            ],
            'effect points of a later effect' => [
                'Slows movement by $s2%.',
                'Slows movement by 50%.',
            ],
            // Damage effects carry a negative value; a description phrases it as an amount
            'a negative effect value is shown as an amount' => [
                'Reduces healing by $s3%.',
                'Reduces healing by 30%.',
            ],
            'duration' => [
                'Stuns the target for $d.',
                'Stuns the target for 8 sec.',
            ],
            'a duration over a minute' => [
                'Lasts $2000d.',
                'Lasts 2 min.',
            ],
            'the periodic tick of an effect' => [
                'Deals damage every $t2 sec.',
                'Deals damage every 3 sec.',
            ],
            // Effects run past nine, and reading only the first digit would show effect 1's value
            // followed by a stray digit - a wrong number rather than an omission
            'a two digit effect index' => [
                'Increases Haste by $s11%.',
                'Increases Haste by 7%.',
            ],
            'radius' => [
                'Hits everything within $a1 yards.',
                'Hits everything within 12 yards.',
            ],
            'total damage over the full duration' => [
                'Bleeds for $o2 damage over $d.',
                'Bleeds for 100 damage over 8 sec.',
            ],
            'a value of another spell' => [
                'Applies a mark for $2000s1 sec.',
                'Applies a mark for 6 sec.',
            ],
            'inline arithmetic' => [
                'Deals ${$s1*2} damage.',
                'Deals 50 damage.',
            ],
            'arithmetic across spells' => [
                'Deals ${$s1*(1+$2000s1/100)} damage.',
                'Deals 26.5 damage.',
            ],
            'a named description variable' => [
                'Deals ${$s1*$<mult>} damage.',
                'Deals 75 damage.',
            ],
            // Nobody is logged in when we render, so player state reads as false - matching what
            // Wowhead shows an anonymous visitor
            'a conditional renders its false branch' => [
                'Strikes the target$?s12345[, and applies a mark][] for $d.',
                'Strikes the target for 8 sec.',
            ],
            'a conditional without a false branch' => [
                'Strikes the target$?s12345[, and applies a mark].',
                'Strikes the target.',
            ],
            'a negated condition renders its true branch' => [
                'Does not work versus Demonic$?!a12345[, Undead,][] or Mechanical beings.',
                'Does not work versus Demonic, Undead, or Mechanical beings.',
            ],
            'a chain of conditions falls through to the else branch' => [
                'Reduces damage taken by $?a1[$s1%]?a2[$s2%][$s3%].',
                'Reduces damage taken by 30%.',
            ],
            'a condition on spell values is evaluated for real' => [
                'Deals damage$?$s1>0[ of $s1][ of none].',
                'Deals damage of 25.',
            ],
            'another spell\'s name' => [
                'Interrupts $@spellname2000.',
                'Interrupts Rending Slash.',
            ],
            // Rendered in the referenced spell's own context, so its $d is that spell's duration
            'another spell\'s whole description' => [
                '$@spelldesc2000',
                'Rends the target for 2 min.',
            ],
            'the plural macro follows the number before it' => [
                'Awards $s4 combo $lpoint:points;.',
                'Awards 1 combo point.',
            ],
            'the plural macro pluralizes' => [
                'Summons $s1 $lminion:minions;.',
                'Summons 25 minions.',
            ],
            'colour codes are dropped but their text is kept' => [
                '|cFFFFFFFFAwards $s4 combo point.|r',
                'Awards 1 combo point.',
            ],
            'hyperlinks keep their text' => [
                'While |Hspell:2000|h $@spellname2000|h is active.',
                'While Rending Slash is active.',
            ],
            // Never leave a raw token in the output - it is nonsense to a reader
            'an unknown token is omitted' => [
                'Stacks up to $u times.',
                'Stacks up to times.',
            ],
            'a value that only a real character has is omitted' => [
                'Deals $s5 Frost damage to the target.',
                'Deals Frost damage to the target.',
            ],
            'arithmetic containing an unknown value is omitted whole' => [
                'Heals for ${$s5*2} health.',
                'Heals for health.',
            ],
            'a reference to a spell we know nothing about is omitted' => [
                'Applies a mark for $999999s1 sec.',
                'Applies a mark for sec.',
            ],
            'carriage returns are normalized' => [
                "First line.\r\n\r\nSecond line.",
                "First line.\n\nSecond line.",
            ],
        ];
    }

    /**
     * A spell with a handful of effects, plus a second spell for the cross-spell references.
     */
    private function createContext(): SpellDescriptionContextInterface
    {
        return new ArraySpellDescriptionContext(
            effects: [
                self::SPELL_ID => [
                    0 => new SpellEffectData(effectType: 2, auraType: 0, basePoints: 25, variance: 0, periodMs: 0, chainTargets: 0, radius: 12, maxRadius: 20),
                    1 => new SpellEffectData(effectType: 6, auraType: 3, basePoints: 50, variance: 0, periodMs: 3000, chainTargets: 0, radius: null, maxRadius: null),
                    2 => new SpellEffectData(effectType: 6, auraType: 22, basePoints: -30, variance: 0, periodMs: 0, chainTargets: 0, radius: null, maxRadius: null),
                    3 => new SpellEffectData(effectType: 6, auraType: 0, basePoints: 1, variance: 0, periodMs: 0, chainTargets: 0, radius: null, maxRadius: null),
                    // A player ability whose damage only exists on a real character
                    4  => new SpellEffectData(effectType: 2, auraType: 0, basePoints: 0, variance: 0, periodMs: 0, chainTargets: 0, radius: null, maxRadius: null),
                    10 => new SpellEffectData(effectType: 6, auraType: 0, basePoints: 7, variance: 0, periodMs: 0, chainTargets: 0, radius: null, maxRadius: null),
                ],
                self::OTHER_SPELL_ID => [
                    0 => new SpellEffectData(effectType: 2, auraType: 0, basePoints: 6, variance: 0, periodMs: 0, chainTargets: 0, radius: null, maxRadius: null),
                ],
            ],
            durationsMs: [
                self::SPELL_ID       => 8000,
                self::OTHER_SPELL_ID => 120000,
            ],
            names: [
                self::OTHER_SPELL_ID => 'Rending Slash',
            ],
            templates: [
                self::OTHER_SPELL_ID => 'Rends the target for $d.',
            ],
            descriptionVariables: [
                self::SPELL_ID => ['mult' => '${3}'],
            ],
        );
    }
}
