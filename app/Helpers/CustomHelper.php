<?php

use Ramsey\Uuid\Uuid;

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
 * @param  $id  string The ID of the alert
 * @return bool True if the user dismissed it, false if they did not yet.
 */
function isAlertDismissed(string $id): bool
{
    return isset($_COOKIE['alert-dismiss-'.$id]);
}

/**
 * Get the initials from a name
 *
 * @param  $name  string
 */
function initials(string $name): string
{
    $explode = explode(' ', $name);
    if (count($explode) > 1) {
        $explode = array_filter($explode, fn ($element) => ! empty($element));

        $result = implode('', array_map(fn ($element) => $element[0], $explode));
    } else {
        $result = substr($name, 0, 2);
    }

    return strtoupper($result);
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
function str_getcsv_assoc(string $csv_string, string $delimiter = ',', bool $skip_empty_lines = true, bool $trim_fields = true)
{
    $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
    $enc = preg_replace_callback(
        '/"(.*?)"/s',
        fn ($field) => urlencode(utf8_encode($field[1])),
        (string) $enc
    );
    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', (string) $enc);

    return array_map(
        function ($line) use ($delimiter, $trim_fields) {
            $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);

            return array_map(
                fn ($field) => str_replace('!!Q!!', '"', utf8_decode(urldecode($field))),
                $fields
            );
        },
        $lines
    );
}
