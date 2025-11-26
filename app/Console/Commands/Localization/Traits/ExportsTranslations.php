<?php

namespace App\Console\Commands\Localization\Traits;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait ExportsTranslations
{
    public function exportTranslations(string $locale, string $fileName, array $data): bool
    {
        $exportToString = $this->arrayToPhpCode($data) . PHP_EOL;

        if (!str_ends_with($fileName, '.php')) {
            $fileName = sprintf('%s.php', $fileName);
        }

        $langPath = lang_path(sprintf('%s/%s', $locale, $fileName));
        if (file_put_contents($langPath, '<?php ' . PHP_EOL . PHP_EOL . 'return ' . $exportToString . ';')) {
            $this->info(sprintf('Translations exported successfully to %s/%s', $locale, $fileName));

            return true;
        } else {
            $this->error(sprintf('Failed to write translations to %s/%s', $locale, $fileName));

            return false;
        }
    }

    /**
     * CHATGPT GENERATED CODE BELOW
     */

    /**
     * Convert a PHP array to a short-array PHP code string with aligned keys.
     * - Short syntax: [ ... ]
     * - 4-space indent
     * - Keys aligned per array level
     * - Trailing commas
     * - Single-quoted strings with proper escaping
     */
    private function arrayToPhpCode(array $data, int $indentLevel = 0): string
    {
        if ($data === []) {
            return '[]';
        }

        $indentUnit = '    '; // 4 spaces
        $indent     = str_repeat($indentUnit, $indentLevel);
        $nextIndent = str_repeat($indentUnit, $indentLevel + 1);

        // Build printable keys first to know max width for alignment
        $printableKeys = [];
        $maxKeyLen     = 0;

        foreach ($data as $key => $_) {
            $keyStr          = $this->formatPhpKey($key);
            $printableKeys[] = $keyStr;
            $len             = strlen($keyStr);
            if ($len > $maxKeyLen) {
                $maxKeyLen = $len;
            }
        }

        $lines = ['['];
        $i     = 0;
        foreach ($data as $key => $value) {
            $keyStr = $printableKeys[$i++];
            $pad    = $maxKeyLen - strlen($keyStr);
            $arrow  = ' => ';

            $valueStr = $this->formatPhpValue($value, $indentLevel + 1);

            // If the value is a multi-line array, put opening on same line:
            // key .... => [
            //     ...
            // ],
            if (is_array($value) && substr($valueStr, 0, 1) === '[' && strpos($valueStr, "\n") !== false) {
                // indent inner lines
                $valueStr = preg_replace('/^/m', $nextIndent, $valueStr);
                // but remove the first extra indent to keep "["
                $valueStr = preg_replace('/^' . preg_quote($nextIndent, '/') . '/', '', $valueStr, 1);

                $lines[] = sprintf(
                    '%s%s%s%s,',
                    $nextIndent,
                    $keyStr,
                    str_repeat(' ', $pad),
                    $arrow . $valueStr,
                );
            } else {
                $lines[] = sprintf(
                    '%s%s%s%s%s,',
                    $nextIndent,
                    $keyStr,
                    str_repeat(' ', $pad),
                    $arrow,
                    $valueStr,
                );
            }
        }

        $lines[] = $indent . ']';

        return implode("\n", $lines);
    }

    private function formatPhpKey($key): string
    {
        if (is_int($key)) {
            return (string)$key;
        }

        // Quote string keys
        return "'" . $this->escapePhpSingleQuoted((string)$key) . "'";
    }

    private function formatPhpValue($value, int $indentLevel): string
    {
        if (is_array($value)) {
            return $this->arrayToPhpCode($value, $indentLevel);
        }
        if (is_string($value)) {
            return "'" . $this->escapePhpSingleQuoted($value) . "'";
        }
        if (is_int($value) || is_float($value)) {
            // Use var_export for numeric edge cases (INF, NAN) but strip "array" forms
            return rtrim(rtrim(var_export($value, true), '0'), '.') ?: '0';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }

        // Fallback: best-effort string
        return "'" . $this->escapePhpSingleQuoted((string)$value) . "'";
    }

    private function escapePhpSingleQuoted(string $s): string
    {
        // In single-quoted PHP strings, only backslash and single-quote are special
        return str_replace(
            [
                "\\",
                "'",
            ],
            [
                "\\\\",
                "\\'",
            ],
            $s,
        );
    }
}
