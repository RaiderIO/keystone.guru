<?php

namespace Tests\Unit\App\Logic\CombatLog;

use App\Logic\CombatLog\CombatLogStringParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class CombatLogStringParserParseBracketedStringTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('CombatLogStringParser')]
    #[Group('ParseBracketedString')]
    #[DataProvider('parseBracketedString_GivenInput_ShouldSplitValuesProperly_DataProvider')]
    public function parseBracketedString_GivenInput_ShouldSplitValuesProperly(string $input, array $expected): void
    {
        $this->assertSame($expected, CombatLogStringParser::parseBracketedString($input));
    }

    public static function parseBracketedString_GivenInput_ShouldSplitValuesProperly_DataProvider(): array
    {
        return [
            // Basic case: simple numeric values
            'simple-numbers' => [
                'input'    => '[(123,456),(789,101)]',
                'expected' => [[123, 456], [789, 101]],
            ],
            // Mixed numbers and strings
            'mixed-values' => [
                'input'    => '[(123,"456 text"),(789,"101")]',
                'expected' => [[123, "456 text"], [789, "101"]],
            ],
            // Nested arrays
            'nested-arrays' => [
                'input'    => '[[(1,2),(3,4)],[(5,[6,7]),"string"],999]',
                'expected' => [[[1, 2], [3, 4]], [[5, [6, 7]], "string"], 999],
            ],
            // Strings with commas inside
            'strings-with-commas' => [
                'input'    => '[["string1",2],["nested, text",3]]',
                'expected' => [["string1", 2], ["nested, text", 3]],
            ],
            // Empty brackets
            'empty-brackets' => [
                'input'    => '[[],[]]',
                'expected' => [[], []],
            ],
            // Single value
            'single-value' => [
                'input'    => '[123]',
                'expected' => [123],
            ],
            // Multiple nested levels
            'deep-nesting' => [
                'input'    => '[[[[123]]]]',
                'expected' => [[[[123]]]],
            ],
            // Empty input
            'empty-input' => [
                'input'    => '[]',
                'expected' => [],
            ],
            // Strings with spaces
            'strings-with-spaces' => [
                'input'    => '[["a string with spaces",123]]',
                'expected' => [["a string with spaces", 123]],
            ],
            // Mixed numeric and non-numeric values
            'numeric-and-non-numeric' => [
                'input'    => '[(123,"abc"),("456",789)]',
                'expected' => [[123, "abc"], ["456", 789]],
            ],
            // Mixed numeric and non-numeric values
            'without-brackets' => [
                'input'    => '(0,0,0,0)',
                'expected' => [0, 0, 0, 0],
            ],
            'unquoted-strings' => [
                'input'    => '[Player-1084-0A6C4CFA,462513,Player-1084-0A912E3E,166646,Player-1084-0AD6D1CD,21562,Player-1084-0A5FC4F3,1126,Player-1084-0A6C4CFA,432021,Player-1084-0A6C4CFA,461857,Player-1084-0A5F83A7,6673,Player-1084-0AF6E8C3,465,Player-1084-0A5F088F,1459,Player-1084-0A5F3C15,462854]',
                'expected' => [
                    'Player-1084-0A6C4CFA',
                    462513,
                    'Player-1084-0A912E3E',
                    166646,
                    'Player-1084-0AD6D1CD',
                    21562,
                    'Player-1084-0A5FC4F3',
                    1126,
                    'Player-1084-0A6C4CFA',
                    432021,
                    'Player-1084-0A6C4CFA',
                    461857,
                    'Player-1084-0A5F83A7',
                    6673,
                    'Player-1084-0AF6E8C3',
                    465,
                    'Player-1084-0A5F088F',
                    1459,
                    'Player-1084-0A5F3C15',
                    462854,
                ],
            ],
        ];
    }
}
