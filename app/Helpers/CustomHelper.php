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

/**
 * Source: https://www.holadevs.com/pregunta/97772/calculate-intersection-of-two-lines-in-php-gd
 *
 * @param $ax1
 * @param $ay1
 * @param $ax2
 * @param $ay2
 * @param $bx1
 * @param $by1
 * @param $bx2
 * @param $by2
 * @return float[]|int[]
 */
function intersection($ax1, $ay1, $ax2, $ay2, $bx1, $by1, $bx2, $by2): ?array
{
    $a1 = $ax1 - $ax2;
    $a2 = $bx1 - $bx2;
    $b1 = $ay1 - $ay2;
    $b2 = $by1 - $by2;
    $c  = ($a1 * $b2) - ($b1 * $a2);
    if (abs($c) > 0.01) { // En caso de que haya interseccion
        $a = ($ax1 * $ay2) - ($ay1 * $ax2);
        $b = ($bx1 * $by2) - ($by1 * $bx2);
        $x = ($a * $a2 - $b * $a1) / $c;
        $y = ($a * $b2 - $b * $b1) / $c;
        return ['x' => $x, 'y' => $y];
    } else { // En caso de que no lo haya
        return null;
    }
}
