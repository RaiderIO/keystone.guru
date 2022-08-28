<?php

/**
 * Checks if a specific alert is already dismissed and thus should not be rendered anymore.
 *
 * @param $id string The ID of the alert
 * @return bool True if the user dismissed it, false if they did not yet.
 */
function isAlertDismissed(string $id): bool
{
    return isset($_COOKIE['alert-dismiss-' . $id]);
}

/**
 * @return string A random hex color.
 */
function randomHexColor(): string
{
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

/**
 * Get the initials from a name
 * @param $name string
 * @return string
 */
function initials(string $name): string
{
    $explode = explode(' ', $name);
    if (count($explode) > 1) {
        $explode = array_filter($explode, function ($element) {
            return !empty($element);
        });

        $result = join('', array_map(function ($element) {
            return $element[0];
        }, $explode));
    } else {
        $result = substr($name, 0, 2);
    }

    return strtoupper($result);
}

/**
 * @param string $string
 * @return bool
 * @link https://stackoverflow.com/a/10797086
 */
function isValidBase64(string $string): bool
{
    return base64_encode(base64_decode($string, true)) === $string;
}


/**
 * @param string $csv_string
 * @param string $delimiter
 * @param bool $skip_empty_lines
 * @param bool $trim_fields
 * @return array|array[][]|false[][]|string[][]|string[][][]
 */
function str_getcsv_assoc(string $csv_string, string $delimiter = ",", bool $skip_empty_lines = true, bool $trim_fields = true)
{
    $enc   = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
    $enc   = preg_replace_callback(
        '/"(.*?)"/s',
        function ($field) {
            return urlencode(utf8_encode($field[1]));
        },
        $enc
    );
    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
    return array_map(
        function ($line) use ($delimiter, $trim_fields) {
            $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
            return array_map(
                function ($field) {
                    return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
                },
                $fields
            );
        },
        $lines
    );
}
