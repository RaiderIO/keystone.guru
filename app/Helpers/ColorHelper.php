<?php

/**
 * @return float[]|int[]
 */
function hsv2rgb($h, $s, $v): array
{
    $f = static function ($n, $k = null) use ($h, $s, $v) {
        if ($k === null) {
            $k = ($n + $h / 60) % 6;
        }

        return $v - $v * $s * max(min($k, 4 - $k, 1), 0);
    };

    return [$f(5), $f(3), $f(1)];
}

function rgb2hsv($r, $g, $b): array
{
    $v = max($r, $g, $b);
    $n = $v - min($r, $g, $b);
    $h = $n <= 0 ? $n : (($v === $r) ? ($g - $b) / $n : (($v === $g) ? 2 + ($b - $r) / $n : 4 + ($r - $g) / $n));

    return [60 * ($h < 0 ? $h + 6 : $h), $v <= 0 ? $v : $n / $v, $v];
}

$GLOBALS['clrLkp'] = [
    ['light blue red', 345], ['blue red', 330], ['magenta', 315], ['blue magenta', 300], ['violet', 285],
    ['indigo', 270], ['blue', 255], ['light green blue', 240], ['green blue', 225], ['green cyan', 210],
    ['blaucyan', 195], ['cyan', 180], ['green cyan', 165], ['blue green', 150], ['light blue-green', 135],
    ['green', 120], ['limett', 105], ['green yellow', 90], ['light green yellow', 75], ['yellow', 60],
    ['safran', 45], ['orange', 30], ['brown', 20], ['vermilion', 15], ['red', 0],
];

/**
 * @return array|float[]|int[]
 *
 * @throws Exception
 */
function hex2rgb($hex): array
{
    $parts = str_split(substr((string)$hex, 1), 2);
    if (!$parts || count($parts) < 3) {
        throw new Exception('Invalid hex value');
    }

    return array_map(static fn($p) => hexdec($p), $parts);
}

/**
 * @return mixed|null
 */
function hsv2name($h, $s, $v)
{
    $result = null;
    foreach ($GLOBALS['clrLkp'] as [$clr, $val]) {
        if ($h >= $val) {
            $result = $clr;
            break;
        }
    }

    return $result;
}

/**
 * @return mixed
 *
 * @throws Exception
 */
function hex2name($hex)
{
    $rgb = hex2rgb($hex);
    $hsv = rgb2hsv(...$rgb);

    $result = null;

    foreach ($GLOBALS['clrLkp'] as [$clr, $val]) {
        if ($hsv[0] >= $val) {
            $result = $clr;
            break;
        }
    }

    return $result;
}

/**
 * @return false|string
 */
function pad($v)
{
    return substr($v . '0', 0, 2);
}

/**
 * @return string
 */
function rgb2hex($r, $g, $b)
{
    return sprintf('#%02x%02x%02x', round($r), round($g), round($b));
}

/**
 * @return string A random hex color.
 */
function randomHexColor(): string
{
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

/**
 * @return string A random hex color.
 */
function randomHexColorNoMapColors(): string
{
    // Keep generating a new color until we don't have an orangeish, brownish or vermillion color
    // to prevent the color from blending into the background map
    do {
        $result = randomHexColor();
    } while (in_array(hex2name($result), ['orange', 'brown', 'vermillion']));

    return $result;
}

function pickHexFromHandlers(array $handlers, float $weight): string {
    assert(count($handlers) > 1, 'Handlers.length <= 1!');

    // If color is before the start or after the end of any gradients, return last known color
    if ($handlers[0][0] >= $weight) {
        return strtolower($handlers[0][1]);
    } elseif ($handlers[count($handlers) - 1][0] <= $weight) {
        return strtolower($handlers[count($handlers) - 1][1]);
    } else {
        // Color is in between gradients, determine which gradient it is
        $color1 = null;
        $color2 = null;
        $scaledWeight = 0.0;

        for ($i = 0; $i < count($handlers) - 1; $i++) {
            $a = $handlers[$i];
            $b = $handlers[$i + 1];

            if ($weight >= $a[0] && $weight <= $b[0]) {
                $color1 = hex2rgb($a[1]);
                $color2 = hex2rgb($b[1]);

                $gradientRange = $b[0] - $a[0];
                $weightOnGradientRange = $weight - $a[0];
                $scaledWeight = $weightOnGradientRange / $gradientRange;

                break;
            }
        }

        assert($color1 !== null, 'color1 === null!');
        assert($color2 !== null, 'color2 === null!');

        $invertedScaledWeight = 1 - $scaledWeight;

        return strtolower(
            rgb2hex(
                round($color2[0] * $scaledWeight + $color1[0] * $invertedScaledWeight),
                round($color2[1] * $scaledWeight + $color1[1] * $invertedScaledWeight),
                round($color2[2] * $scaledWeight + $color1[2] * $invertedScaledWeight)
            )
        );
    }
}
