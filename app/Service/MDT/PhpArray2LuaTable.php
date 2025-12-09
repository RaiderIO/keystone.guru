<?php

namespace App\Service\MDT;

class PhpArray2LuaTable
{
    private const string TOKEN_OBJECT_OPEN = '{' . PHP_EOL;

    private const string TOKEN_OBJECT_CLOSE = '}';

    private const string TOKEN_ARRAY_KEY_OPEN = '[';

    private const string TOKEN_ARRAY_KEY_CLOSE = ']';

    private const string TOKEN_ASSIGNMENT_OPERATOR = '=';

    private const string TOKEN_ITEM_SEPARATOR = ',' . PHP_EOL;

    private const string TOKEN_OBJECT_CLOSE_END_SEPARATOR = ';' . PHP_EOL;

    private const string TOKEN_STRING_QUOTE = '"';

    private const string TOKEN_WHITESPACE = ' ';

    private const int INDENT_SIZE = 2;

    public function toLuaTableString(string $tableName, array $contents): string
    {
        return sprintf(
            '
%s %s %s',
            $tableName,
            self::TOKEN_ASSIGNMENT_OPERATOR,
            implode('', $this->arrayToLuaTokens($contents)),
        );
    }

    private function arrayToLuaTokens(array $array, int $indent = 0): array
    {
        $indent++;
        $tokens = [self::TOKEN_OBJECT_OPEN];

        foreach ($array as $key => $value) {
            // Array key
            $tokens = array_merge($tokens, $this->renderArrayKey($key, $indent));

            // Equals..
            $tokens[] = self::TOKEN_WHITESPACE;
            $tokens[] = self::TOKEN_ASSIGNMENT_OPERATOR;
            $tokens[] = self::TOKEN_WHITESPACE;

            // Object (recursion) or value
            if (is_array($value)) {
                $tokens = array_merge($tokens, $this->arrayToLuaTokens($value, $indent));
            } else {
                $tokens = array_merge($tokens, $this->renderValue($value, $indent));
                // Need this separator to close off values as they come in an array
                $tokens[] = self::TOKEN_ITEM_SEPARATOR;
            }
        }

        $tokens[] = $this->getIndent($indent - 1);
        $tokens[] = self::TOKEN_OBJECT_CLOSE;
        // End with a semicolon when we close off the object, otherwise just a normal separator
        $tokens[] = $indent === 1 ? self::TOKEN_OBJECT_CLOSE_END_SEPARATOR : self::TOKEN_ITEM_SEPARATOR;

        return $tokens;
    }

    private function renderArrayKey($key, int $indent): array
    {
        return [
            $this->getIndent($indent),
            self::TOKEN_ARRAY_KEY_OPEN,
            ...$this->renderValue($key, $indent),
            self::TOKEN_ARRAY_KEY_CLOSE,
        ];
    }

    private function renderValue($value, int $indent): array
    {
        if (is_string($value)) {
            $tokens = [
                self::TOKEN_STRING_QUOTE,
                $value,
                self::TOKEN_STRING_QUOTE,
            ];
        } elseif (is_bool($value)) {
            $tokens = [$value ? 'true' : 'false'];
        } else {
            // ints, floats, etc
            $tokens = [$value];
        }

        return $tokens;
    }

    private function getIndent(int $indent): string
    {
        if ($indent <= 0) {
            return '';
        }

        return str_repeat(' ', self::INDENT_SIZE * $indent);
    }
}
