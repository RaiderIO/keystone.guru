<?php

namespace App\Logic\CombatLog;

use InvalidArgumentException;

class CombatLogStringParser
{
    /**
     * @param string $line
     * @return array
     */
    public static function parseCombatLogLine(string $line): array
    {
        $result           = [];
        $current          = '';
        $bracketLevel     = 0;
        $parenthesisLevel = 0;
        $inQuotes         = false;

        $length = strlen($line);
        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($char === '"' && ($i === 0 || $line[$i - 1] !== '\\')) {
                // Toggle quote state
                $inQuotes = !$inQuotes;
                $current  .= $char;
            } else if ($char === '[' && !$inQuotes) {
                // Enter a bracket level
                $bracketLevel++;
                $current .= $char;
            } else if ($char === ']' && !$inQuotes) {
                // Exit a bracket level
                $bracketLevel--;
                $current .= $char;
            } else if ($char === ',' && !$inQuotes && $bracketLevel === 0) {
                // Split on commas outside brackets and quotes
                $result[] = trim(trim($current), '"');
                $current  = '';
            } else {
                if ($char === '(' && !$inQuotes) {
                    $parenthesisLevel++;
                } else if ($char === ')' && !$inQuotes) {
                    $parenthesisLevel--;
                }
                // Add character to the current value
                $current .= $char;
            }
        }

        if ($inQuotes) {
            throw new InvalidArgumentException(sprintf('Unbalanced quotes in string %s', $line));
        }

        if ($bracketLevel !== 0) {
            throw new InvalidArgumentException(sprintf('Unbalanced brackets in string %s', $line));
        }

        if ($parenthesisLevel !== 0) {
            throw new InvalidArgumentException(sprintf('Unbalanced parenthesis in string %s', $line));
        }

        // Add the last value
        if ($current !== '') {
            $current  = trim(trim($current), '"');
            $result[] = $current;
        }

        return $result;
    }

    public static function parseBracketedString(string $input): array
    {
        // Remove any outer brackets and trim spaces
        $input = trim($input);

        // Check for empty input
        if ($input === '[]') {
            return [];
        }

        $hadBrackets = false;
        // Remove the outermost brackets and handle the content inside
        if ($input[0] === '[' && $input[-1] === ']') {
            $input       = substr($input, 1, -1);  // Remove the first and last brackets
            $hadBrackets = true;
        }

        // Use a recursive function to parse the content
        $parsed = self::parseContent($input);

        return !$hadBrackets && is_array($parsed[0]) ? $parsed[0] : $parsed;
    }

    private static function parseContent(string $content): array
    {
        $result = [];
        $length = strlen($content);
        $i      = 0;

        while ($i < $length) {
            // Skip spaces
            if (ctype_space($content[$i])) {
                $i++;
                continue;
            }

            // Handle opening bracket '['
            if ($content[$i] === '[') {
                $nestedContent = self::extractBracketedContent($content, $i);
                $result[]      = self::parseContent($nestedContent); // Recursively parse the nested content
                continue;
            }

            // Handle a value (number or string)
            if ($content[$i] === '"') {
                $string   = self::extractString($content, $i);
                $result[] = $string;
                continue;
            }

            // Handle numbers
            if (is_numeric($content[$i])) {
                $number   = self::extractNumber($content, $i);
                $result[] = $number;
                continue;
            }

            // Handle opening parenthesis '(' for tuple-like structures
            if ($content[$i] === '(') {
                $tupleContent = self::extractTupleContent($content, $i);
                $result[]     = self::parseContent($tupleContent); // Parse the tuple content
                continue;
            }

            $i++; // Move to the next character
        }

        return $result;
    }

    private static function extractBracketedContent(string $content, int &$i): string
    {
        $depth  = 1;
        $start  = ++$i;
        $length = strlen($content);

        // Find the matching closing bracket for the opening '['
        while ($depth > 0 && $i < $length) {
            if ($content[$i] === '[') {
                $depth++;
            } else if ($content[$i] === ']') {
                $depth--;
            }
            $i++;
        }

        return substr($content, $start, $i - $start - 1); // Extract the bracketed content
    }

    private static function extractTupleContent(string $content, int &$i): string
    {
        $depth  = 1;
        $start  = ++$i;
        $length = strlen($content);

        // Find the matching closing parenthesis for the opening '('
        while ($depth > 0 && $i < $length) {
            if ($content[$i] === '(') {
                $depth++;
            } else if ($content[$i] === ')') {
                $depth--;
            }
            $i++;
        }

        return substr($content, $start, $i - $start - 1); // Extract the tuple content
    }

    private static function extractString(string $content, int &$i): string
    {
        $start  = ++$i; // Skip the opening quote
        $length = strlen($content);
        while ($i < $length && $content[$i] !== '"') {
            $i++;
        }
        $string = substr($content, $start, $i - $start);
        $i++; // Skip the closing quote

        return $string;
    }

    private static function extractNumber(string $content, int &$i): int
    {
        $start = $i;
        while ($i < strlen($content) && is_numeric($content[$i])) {
            $i++;
        }

        return (int)substr($content, $start, $i - $start);
    }

}
