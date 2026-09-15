<?php

namespace App\Service\Spell\Description;

/**
 * A deliberately small recursive descent evaluator for the arithmetic embedded in spell descriptions
 * (`${$s2*1.5}`). Descriptions come from an external data source, so this never goes near `eval()`:
 * anything the grammar below does not describe fails the evaluation instead.
 *
 * Grammar (loosest to tightest):
 *   ternary    := comparison ( '?' ternary ':' ternary )?
 *   comparison := sum ( ('<' | '>' | '<=' | '>=' | '=' | '==' | '!=') sum )?
 *   sum        := product ( ('+' | '-') product )*
 *   product    := unary ( ('*' | '/' | '%') unary )*
 *   unary      := '-'? primary
 *   primary    := number | '(' ternary ')' | function '(' ternary ( ',' ternary )* ')'
 */
class MathExpressionEvaluator
{
    private const array FUNCTIONS = ['floor', 'ceil', 'round', 'abs', 'min', 'max'];

    private string $expression = '';

    private int $offset = 0;

    /**
     * Evaluate the expression, or return null when it is not valid arithmetic - an unresolvable spell
     * description token that was replaced by nothing typically lands here.
     */
    public function evaluate(string $expression): ?float
    {
        $this->expression = $expression;
        $this->offset     = 0;

        $result = $this->parseTernary();

        $this->skipWhitespace();

        // Trailing junk means we did not understand the expression after all
        if ($result === null || $this->offset < strlen($this->expression)) {
            return null;
        }

        return $result;
    }

    private function parseTernary(): ?float
    {
        $condition = $this->parseComparison();

        if ($condition === null || !$this->consume('?')) {
            return $condition;
        }

        $whenTrue = $this->parseTernary();

        if ($whenTrue === null || !$this->consume(':')) {
            return null;
        }

        $whenFalse = $this->parseTernary();

        if ($whenFalse === null) {
            return null;
        }

        return $condition !== 0.0 ? $whenTrue : $whenFalse;
    }

    private function parseComparison(): ?float
    {
        $left = $this->parseSum();

        if ($left === null) {
            return null;
        }

        foreach (['<=', '>=', '==', '!=', '<', '>', '='] as $operator) {
            if (!$this->consume($operator)) {
                continue;
            }

            $right = $this->parseSum();

            if ($right === null) {
                return null;
            }

            return (float)match ($operator) {
                '<'       => $left < $right,
                '>'       => $left > $right,
                '<='      => $left <= $right,
                '>='      => $left >= $right,
                '!='      => $left !== $right,
                '=', '==' => $left === $right,
            };
        }

        return $left;
    }

    private function parseSum(): ?float
    {
        $result = $this->parseProduct();

        if ($result === null) {
            return null;
        }

        while (true) {
            if ($this->consume('+')) {
                $operand = $this->parseProduct();
            } elseif ($this->consume('-')) {
                $operand = $this->parseProduct();
                $operand = $operand === null ? null : -$operand;
            } else {
                return $result;
            }

            if ($operand === null) {
                return null;
            }

            $result += $operand;
        }
    }

    private function parseProduct(): ?float
    {
        $result = $this->parseUnary();

        if ($result === null) {
            return null;
        }

        while (true) {
            if ($this->consume('*')) {
                $operator = '*';
            } elseif ($this->consume('/')) {
                $operator = '/';
            } elseif ($this->consume('%')) {
                $operator = '%';
            } else {
                return $result;
            }

            $operand = $this->parseUnary();

            if ($operand === null || ($operator !== '*' && $operand === 0.0)) {
                return null;
            }

            $result = match ($operator) {
                '*' => $result * $operand,
                '/' => $result / $operand,
                '%' => fmod($result, $operand),
            };
        }
    }

    private function parseUnary(): ?float
    {
        if ($this->consume('-')) {
            $value = $this->parseUnary();

            return $value === null ? null : -$value;
        }

        // A leading plus is meaningless but harmless
        $this->consume('+');

        return $this->parsePrimary();
    }

    private function parsePrimary(): ?float
    {
        $this->skipWhitespace();

        if ($this->consume('(')) {
            $value = $this->parseTernary();

            return $value !== null && $this->consume(')') ? $value : null;
        }

        foreach (self::FUNCTIONS as $function) {
            if (!$this->consumeFunction($function)) {
                continue;
            }

            $arguments = [];

            do {
                $argument = $this->parseTernary();

                if ($argument === null) {
                    return null;
                }

                $arguments[] = $argument;
            } while ($this->consume(','));

            if (!$this->consume(')')) {
                return null;
            }

            return $this->applyFunction($function, $arguments);
        }

        if (preg_match('/\G(?:\d+(?:\.\d+)?|\.\d+)/', $this->expression, $matches, 0, $this->offset) !== 1) {
            return null;
        }

        $this->offset += strlen($matches[0]);

        return (float)$matches[0];
    }

    /** @param array<int, float> $arguments */
    private function applyFunction(string $function, array $arguments): ?float
    {
        $singleArgument    = count($arguments) === 1;
        $multipleArguments = count($arguments) >= 2;

        return match (true) {
            $function === 'floor' && $singleArgument  => floor($arguments[0]),
            $function === 'ceil' && $singleArgument   => ceil($arguments[0]),
            $function === 'round' && $singleArgument  => round($arguments[0]),
            $function === 'abs' && $singleArgument    => abs($arguments[0]),
            $function === 'min' && $multipleArguments => min($arguments),
            $function === 'max' && $multipleArguments => max($arguments),
            default                                   => null,
        };
    }

    private function consume(string $operator): bool
    {
        $this->skipWhitespace();

        if (!str_starts_with(substr($this->expression, $this->offset), $operator)) {
            return false;
        }

        $this->offset += strlen($operator);

        return true;
    }

    private function consumeFunction(string $function): bool
    {
        $this->skipWhitespace();

        if (preg_match(sprintf('/\G%s\s*\(/i', preg_quote($function, '/')), $this->expression, $matches, 0, $this->offset) !== 1) {
            return false;
        }

        $this->offset += strlen($matches[0]);

        return true;
    }

    private function skipWhitespace(): void
    {
        while ($this->offset < strlen($this->expression) && ctype_space($this->expression[$this->offset])) {
            $this->offset++;
        }
    }
}
