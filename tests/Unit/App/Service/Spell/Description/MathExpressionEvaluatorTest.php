<?php

namespace Tests\Unit\App\Service\Spell\Description;

use App\Service\Spell\Description\MathExpressionEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('SpellDescription')]
final class MathExpressionEvaluatorTest extends TestCase
{
    #[Test]
    #[DataProvider('validExpressionProvider')]
    public function evaluate_givenValidExpression_returnsResult(string $expression, float $expected): void
    {
        // Arrange
        $evaluator = new MathExpressionEvaluator();

        // Act
        $result = $evaluator->evaluate($expression);

        // Assert - the delta comparison treats null as 0.0, so a rejected expression would pass every row expecting 0
        $this->assertNotNull($result, $expression);
        $this->assertEqualsWithDelta($expected, $result, 0.0001, $expression);
    }

    /** @return array<string, array{string, float}> */
    public static function validExpressionProvider(): array
    {
        return [
            'addition'                 => ['1+2', 3.0],
            'operator precedence'      => ['2+3*4', 14.0],
            'parentheses'              => ['(2+3)*4', 20.0],
            'division'                 => ['10/4', 2.5],
            'negative operand'         => ['(-5)*2', -10.0],
            'subtracting a negative'   => ['2-(-5)', 7.0],
            'decimals'                 => ['0.5*8', 4.0],
            'whitespace'               => [' 100 * 1.5 ', 150.0],
            'floor'                    => ['floor(7/2)', 3.0],
            'min of three'             => ['min(9, 4, 6)', 4.0],
            'max'                      => ['max(9, 4)', 9.0],
            'comparison is true'       => ['3>2', 1.0],
            'comparison is false'      => ['3<2', 0.0],
            'ternary takes true side'  => ['3>2?10:20', 10.0],
            'ternary takes false side' => ['3<2?10:20', 20.0],
            'nested spell math'        => ['(20)*(1+(50)/100)', 30.0],
            'ceil'                     => ['ceil(7/2)', 4.0],
            'round'                    => ['round(2.4)', 2.0],
            'abs'                      => ['abs(3-5)', 2.0],
            'modulo'                   => ['7%3', 1.0],
            'less than or equal'       => ['2<=2', 1.0],
            'greater than or equal'    => ['2>=2', 1.0],
            'equal'                    => ['2==2', 1.0],
            'single equals compares'   => ['2=3', 0.0],
            'not equal'                => ['2!=3', 1.0],
        ];
    }

    #[Test]
    #[DataProvider('invalidExpressionProvider')]
    public function evaluate_givenInvalidExpression_returnsNull(string $expression): void
    {
        // Arrange
        $evaluator = new MathExpressionEvaluator();

        // Act
        $result = $evaluator->evaluate($expression);

        // Assert
        $this->assertNull($result, $expression);
    }

    /** @return array<string, array{string}> */
    public static function invalidExpressionProvider(): array
    {
        return [
            'empty'                     => [''],
            'an unresolved token'       => ['$s1*2'],
            'a gap left by an omission' => ['*2'],
            'unbalanced parentheses'    => ['(2+3'],
            'trailing operator'         => ['2+'],
            'division by zero'          => ['2/0'],
            'unknown function'          => ['sqrt(4)'],
            'modulo by zero'            => ['5%0'],
            'floor of two arguments'    => ['floor(7, 2)'],
            'min of a single argument'  => ['min(4)'],
            'a ternary without a colon' => ['3>2?10'],
            // The templates are externally sourced, so anything that is not arithmetic must simply fail
            'php code'       => ['phpinfo()'],
            'a shell string' => ['`ls`'],
        ];
    }
}
