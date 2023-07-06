<?php


namespace App\Logic\Utils;

class MathUtils
{
    /**
     * @param float $x1
     * @param float $x2
     * @param float $y1
     * @param float $y2
     * @return float
     */
    public static function distanceBetweenPoints(float $x1, float $x2, float $y1, float $y2): float
    {
        // Pythagoras theorem: a^2+b^2=c^2
        return sqrt(
            pow($x1 - $x2, 2) +
            pow($y1 - $y2, 2)
        );
    }
}
