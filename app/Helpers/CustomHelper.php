<?php

use App\Logic\Utils\MathUtils;

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
 * @param array{lng: float, lat: float} $p1
 * @param array{lng: float, lat: float} $p2
 * @param array{lng: float, lat: float} $p3
 * @param array{lng: float, lat: float} $p4
 * @return array{lng: float, lat: float}|null
 */
function intersection(array $p1, array $p2, array $p3, array $p4): ?array
{
    // Line AB represented as a1lng + b1lat = c1
    $a1 = $p2['lat'] - $p1['lat'];
    $b1 = $p1['lng'] - $p2['lng'];
    $c1 = $a1 * ($p1['lng']) + $b1 * ($p1['lat']);

    // Line CD represented as a2lng + b2lat = c2
    $a2 = $p4['lat'] - $p3['lat'];
    $b2 = $p3['lng'] - $p4['lng'];
    $c2 = $a2 * ($p3['lng']) + $b2 * ($p3['lat']);

    $determinant = $a1 * $b2 - $a2 * $b1;

    if ($determinant == 0) {
        // The lines are parallel and will never intersect
        return null;
    } else {
        $lng = ($b2 * $c1 - $b1 * $c2) / $determinant;
        $lat = ($a1 * $c2 - $a2 * $c1) / $determinant;

        $l1Length = MathUtils::distanceBetweenPoints($p1['lng'], $p2['lng'], $p1['lat'], $p2['lat']);
        // If the distance to the found point is greater than the length of EITHER of the lines, it's not a correct intersection!
        // This means that the intersection occurred in the extended line past the points of $p1 and $p2. We don't want them.
        if ($l1Length < MathUtils::distanceBetweenPoints($p1['lng'], $lng, $p1['lat'], $lat) ||
            $l1Length < MathUtils::distanceBetweenPoints($p2['lng'], $lng, $p2['lat'], $lat)
        ) {
            return null;
        }

        $l2Length = MathUtils::distanceBetweenPoints($p3['lng'], $p4['lng'], $p3['lat'], $p4['lat']);
        if ($l2Length < MathUtils::distanceBetweenPoints($p3['lng'], $lng, $p3['lat'], $lat) ||
            $l2Length < MathUtils::distanceBetweenPoints($p4['lng'], $lng, $p4['lat'], $lat)
        ) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }
}

/**
 * @param array $point
 * @param array $polygon
 * @return bool
 */
function polygonContainsPoint(array $point, array $polygon)
{
    if ($polygon[0] != $polygon[count($polygon) - 1]) {
        $polygon[] = $polygon[0];
    }
    $j        = 0;
    $oddNodes = false;
    $lat      = $point['lat'];
    $lng      = $point['lng'];
    $n        = count($polygon);
    for ($i = 0; $i < $n; $i++) {
        $j++;
        if ($j == $n) {
            $j = 0;
        }
        if ((($polygon[$i]['lng'] < $lng) && ($polygon[$j]['lng'] >= $lng)) || (($polygon[$j]['lng'] < $lng) && ($polygon[$i]['lng'] >=
                    $lng))) {
            if ($polygon[$i]['lat'] + ($lng - $polygon[$i]['lng']) / ($polygon[$j]['lng'] - $polygon[$i]['lng']) * ($polygon[$j]['lat'] -
                    $polygon[$i]['lat']) < $lat) {
                $oddNodes = !$oddNodes;
            }
        }
    }
    return $oddNodes;
}
