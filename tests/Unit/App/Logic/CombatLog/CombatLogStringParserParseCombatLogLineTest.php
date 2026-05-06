<?php

namespace Tests\Unit\App\Logic\CombatLog;

use App\Logic\CombatLog\CombatLogStringParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class CombatLogStringParserParseCombatLogLineTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('CombatLogStringParser')]
    #[Group('ParseCombatLogLine')]
    #[DataProvider('parseCombatLogLine_GivenLine_ShouldParseCorrectly_DataProvider')]
    public function parseCombatLogLine_GivenLine_ShouldParseCorrectly(string $line, array $expected): void
    {
        $this->assertEquals($expected, CombatLogStringParser::parseCombatLogLine($line));
    }

    public static function parseCombatLogLine_GivenLine_ShouldParseCorrectly_DataProvider(): array
    {
        return [
            'Basic Parsing' => [
                'line'     => '10/18/2024 21:34:24.8572,COMBATANT_INFO,Player-1084-0AE6DF11,0,14644',
                'expected' => [
                    '10/18/2024 21:34:24.8572',
                    'COMBATANT_INFO',
                    'Player-1084-0AE6DF11',
                    '0',
                    '14644',
                ],
            ],
            'Quoted Values' => [
                'line'     => '10/18/2024 21:34:24.8572,"COMBATANT_INFO","Player,1084-0AE6DF11",0,14644',
                'expected' => [
                    '10/18/2024 21:34:24.8572',
                    'COMBATANT_INFO',
                    'Player,1084-0AE6DF11',
                    '0',
                    '14644',
                ],
            ],
            'Nested Brackets' => [
                'line'     => '[(123,456),(789,101)],[(112,223)]',
                'expected' => [
                    '[(123,456),(789,101)]',
                    '[(112,223)]',
                ],
            ],
            'Combined Quotes and Brackets' => [
                'line'     => '"Data,1",[(123,"456,789"),(101,202)],"End"',
                'expected' => [
                    'Data,1',
                    '[(123,"456,789"),(101,202)]',
                    'End',
                ],
            ],
            'Escaped Quotes' => [
                'line'     => '"Data with \\"escaped\\" quotes",123',
                'expected' => [
                    'Data with \\"escaped\\" quotes',
                    '123',
                ],
            ],
            'Deeply Nested Brackets' => [
                'line'     => '[[(1,2),(3,4)],[(5,[6,7])]],123',
                'expected' => [
                    '[[(1,2),(3,4)],[(5,[6,7])]]',
                    '123',
                ],
            ],
            'Empty Line' => [
                'line'     => '',
                'expected' => [],
            ],
            'No Brackets or Quotes' => [
                'line'     => '10/18/2024,COMBATANT_INFO,Player-1084-0AE6DF11',
                'expected' => [
                    '10/18/2024',
                    'COMBATANT_INFO',
                    'Player-1084-0AE6DF11',
                ],
            ],
            'Mixed Content and Large Input' => [
                'line'     => '10/18/2024 21:34:24.8572,COMBATANT_INFO,[(123,456),(789,101)],"Player-1084-0AE6DF11",0,[("Nested,1"),("Nested,2")],14644',
                'expected' => [
                    '10/18/2024 21:34:24.8572',
                    'COMBATANT_INFO',
                    '[(123,456),(789,101)]',
                    'Player-1084-0AE6DF11',
                    '0',
                    '[("Nested,1"),("Nested,2")]',
                    '14644',
                ],
            ],
            'Failing Test' => [
                'line'     => '10/18/2024 21:34:24.8572,COMBATANT_INFO,[(102380,126443,1)],(0,0,0,0),[(211024,606,())]',
                'expected' => [
                    '10/18/2024 21:34:24.8572',
                    'COMBATANT_INFO',
                    '[(102380,126443,1)]',
                    '(0,0,0,0)',
                    '[(211024,606,())]',
                ],
            ],
        ];
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('CombatLogStringParser')]
    #[Group('ParseCombatLogLine')]
    #[DataProvider('parseCombatLogLine_GivenInvalidLine_ShouldThrowInvalidArgumentException_DataProvider')]
    public function parseCombatLogLine_GivenInvalidLine_ShouldThrowInvalidArgumentException(string $line): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombatLogStringParser::parseCombatLogLine($line);
    }

    public static function parseCombatLogLine_GivenInvalidLine_ShouldThrowInvalidArgumentException_DataProvider(): array
    {
        return [
            'Malformed Input (Unbalanced Brackets)' => [
                'line' => '[(123,456),(789,101)',
            ],
            'Malformed Input (Unbalanced Parenthesis)' => [
                'line' => '[(123,456),(789,101]',
            ],
            'Malformed Input (Unbalanced Quotes)' => [
                'line' => '"Data,123',
            ],
        ];
    }
}
