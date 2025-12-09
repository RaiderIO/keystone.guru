<?php

use Ramsey\Uuid\Uuid;
use Swoole\Http\Server;

$GLOBALS['correlationId'] = Uuid::uuid4()->toString();

/**
 * @return string The current correlationID of the request.
 */
function correlationId(): string
{
    return $GLOBALS['correlationId'];
}

/**
 * Checks if a specific alert is already dismissed and thus should not be rendered anymore.
 *
 * @param       $id string The ID of the alert
 * @return bool True if the user dismissed it, false if they did not yet.
 */
function isAlertDismissed(string $id): bool
{
    return isset($_COOKIE['alert-dismiss-' . $id]);
}

/**
 * Get the initials from a name
 */
function initials(string $name): string
{
    // Ensure we're working in UTF‑8
    $name = trim($name);

    if ($name === '') {
        return '';
    }

    $parts = preg_split('/\s+/u', $name) ?: [];

    if (count($parts) > 1) {
        // Take the first character of each word (multibyte‑safe)
        $letters = array_map(
            static fn(string $part) => mb_substr($part, 0, 1, 'UTF-8'),
            $parts,
        );
        $result = implode('', $letters);
    } else {
        // Single word: first two characters (multibyte‑safe)
        $result = mb_substr($name, 0, 2, 'UTF-8');
    }

    return mb_strtoupper($result, 'UTF-8');
}

/**
 * @link https://stackoverflow.com/a/10797086
 */
function isValidBase64(string $string): bool
{
    return base64_encode(base64_decode($string, true)) === $string;
}

/**
 * @return array|array[][]|false[][]|string[][]|string[][][]
 */
function str_getcsv_assoc(
    string $csv_string,
    string $delimiter = ',',
    bool   $skip_empty_lines = true,
    bool   $trim_fields = true,
) {
    $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
    $enc = preg_replace_callback(
        '/"(.*?)"/s',
        static fn($field) => urlencode(mb_convert_encoding($field[1], 'UTF-8', 'ISO-8859-1')),
        (string)$enc,
    );
    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', (string)$enc);

    return array_map(
        static function ($line) use ($delimiter, $trim_fields) {
            $fields = $trim_fields ? array_map(trim(...), explode($delimiter, $line)) : explode($delimiter, $line);

            return array_map(
                static fn($field) => str_replace('!!Q!!', '"', mb_convert_encoding(urldecode($field), 'ISO-8859-1')),
                $fields,
            );
        },
        $lines,
    );
}

if (!function_exists('servedByOctane')) {
    function servedByOctane(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE']) && ((int)$_SERVER['LARAVEL_OCTANE'] === 1);
    }
}

if (!function_exists('onSwooleServer')) {
    function onSwooleServer(): bool
    {
        return (extension_loaded('swoole') || extension_loaded('openswoole')) && app()->bound(Server::class);
    }
}

function ksgAsset(string $path): string
{
    return sprintf('%s/%s', config('keystoneguru.assets_base_url'), $path);
}

/**
 * Retains functionality of locally compiled assets but also allows for assets to be served from a CDN in non-local environments.
 *
 * @param  string $path
 * @return string
 */
function ksgCompiledAsset(string $path): string
{
    if (app()->environment('local')) {
        return asset($path);
    }

    $revision = trim(file_get_contents(base_path('version')));

    return sprintf('%s/compiled/%s/%s', config('keystoneguru.assets_base_url'), $revision, $path);
}

function ksgAssetImage(string $path = ''): string
{
    return sprintf('%s/%s', config('keystoneguru.images_base_url'), trim($path, '/'));
}

/**
 * Insert an associative array after a specific key (string-keyed arrays).
 * If $afterKey isn't found, the $insert array is appended at the end.
 * For duplicate keys, values from $insert take precedence.
 */
function array_insert_after(array $array, string $afterKey, array $insert): array
{
    if ($insert === []) {
        return $array;
    }

    $keys = array_keys($array);
    $pos  = array_search($afterKey, $keys, true);

    if ($pos === false) {
        // Append; let $insert override duplicates
        return array_merge(
            array_diff_key($array, $insert),
            $insert,
        );
    }

    $before = array_slice($array, 0, $pos + 1, true);
    $after  = array_slice($array, $pos + 1, null, true);

    // Ensure $insert wins on duplicates by removing overlapping keys first
    $before = array_diff_key($before, $insert);
    $after  = array_diff_key($after, $insert);

    return array_merge($before, $insert, $after);
}
