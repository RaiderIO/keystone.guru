<?php


namespace App\Service\MDT;

class PhpArray2LuaTable
{
    private const TOKEN_OBJECT_OPEN  = '{' . PHP_EOL;
    private const TOKEN_OBJECT_CLOSE = '}';

    private const TOKEN_ARRAY_KEY_OPEN  = '[';
    private const TOKEN_ARRAY_KEY_CLOSE = ']';

    private const TOKEN_ASSIGNMENT_OPERATOR = '=';

    private const TOKEN_ITEM_SEPARATOR = ';' . PHP_EOL;

    private const TOKEN_STRING_QUOTE = '"';

    /**
     * @param string $tableName
     * @param array $contents
     * @return string
     */
    public function toLuaTableString(string $tableName, array $contents): string
    {
        return sprintf('
%s %s %s
        ', $tableName, self::TOKEN_ASSIGNMENT_OPERATOR, implode('', $this->arrayToLuaTokens($contents)));
    }

    /**
     * @param array $array
     * @return array
     */
    private function arrayToLuaTokens(array $array): array
    {
        $tokens = [self::TOKEN_OBJECT_OPEN];

        foreach ($array as $key => $value) {
            // Array key
            $tokens = array_merge($tokens, $this->renderArrayKey($key));

            // Equals..
            $tokens[] = self::TOKEN_ASSIGNMENT_OPERATOR;

            // Object (recursion) or value
            if (is_array($value)) {
                $tokens = array_merge($tokens, $this->arrayToLuaTokens($value));
            } else {
                $tokens = array_merge($tokens, $this->renderValue($value));
                // Need this separator to close off values as they come in an array
                $tokens[] = self::TOKEN_ITEM_SEPARATOR;
            }
        }

        $tokens[] = self::TOKEN_OBJECT_CLOSE;
        $tokens[] = self::TOKEN_ITEM_SEPARATOR;

        return $tokens;
    }

    /**
     * @param $key
     * @return array
     */
    private function renderArrayKey($key): array
    {
        return [self::TOKEN_ARRAY_KEY_OPEN, ...$this->renderValue($key), self::TOKEN_ARRAY_KEY_CLOSE];
    }

    /**
     * @param $value
     * @return array
     */
    private function renderValue($value): array
    {
        if (is_string($value)) {
            $tokens = [self::TOKEN_STRING_QUOTE, $value, self::TOKEN_STRING_QUOTE];
        } else if (is_bool($value)) {
            $tokens = [$value ? 'true' : 'false'];
        } else {
            // ints, floats, etc
            $tokens = [$value];
        }

        return $tokens;
    }
}
