<?php

namespace App\Service\RaiderIO\Mock;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Builds a continent heatmap grid response shaped like the Raider.IO grid endpoint's (floorsAsArray on), with
 * clustered deaths on every zone floor of the continent. The same seed always produces the same fixture.
 */
class ContinentHeatmapFixtureGenerator
{
    private const int HOTSPOT_SIGMA_MIN_CELLS = 2;

    private const int HOTSPOT_SIGMA_MAX_CELLS = 8;

    private const int HOTSPOT_PEAK_MIN = 3;

    private const int HOTSPOT_PEAK_MAX = 200;

    /** Stops a zone whose target exceeds its own cell count from looping forever */
    private const int MAX_HOTSPOTS_PER_ZONE = 10000;

    /**
     * @return array{gridsByFloor: array<int, list<float|int>>, numRuns: int, maxSamplesInGrid: int}
     */
    public function generate(
        Dungeon $dungeon,
        int     $cellsPerZone,
        int     $gridSizeX,
        int     $gridSizeY,
        int     $seed,
    ): array {
        $randomizer = new Randomizer(new Mt19937($seed));

        $gridsByFloor     = [];
        $numRuns          = 0;
        $maxSamplesInGrid = 0;
        foreach ($dungeon->floors->sortBy('id') as $floor) {
            /** @var Floor $floor */
            if ($floor->facade) {
                continue;
            }

            $counts = $this->generateZoneCounts($randomizer, min($cellsPerZone, $gridSizeX * $gridSizeY), $gridSizeX, $gridSizeY);

            $stepX = ($floor->ingame_max_x - $floor->ingame_min_x) / $gridSizeX;
            $stepY = ($floor->ingame_max_y - $floor->ingame_min_y) / $gridSizeY;

            $rows = [];
            ksort($counts);
            foreach ($counts as $cellIndex => $count) {
                $gridX = intdiv($cellIndex, $gridSizeY);
                $gridY = $cellIndex % $gridSizeY;

                // Same cell origin as CoordinatesService::calculateGridLocationForIngameLocation()
                $rows[] = round($floor->ingame_min_x + $gridX * $stepX, 2);
                $rows[] = round($floor->ingame_min_y + $gridY * $stepY, 2);
                $rows[] = $count;

                $numRuns += $count;
                $maxSamplesInGrid = max($maxSamplesInGrid, $count);
            }

            $gridsByFloor[$floor->id] = $rows;
        }

        return [
            'gridsByFloor'     => $gridsByFloor,
            'numRuns'          => $numRuns,
            'maxSamplesInGrid' => $maxSamplesInGrid,
        ];
    }

    /**
     * Drops gaussian hotspots on the grid until at least $targetCells cells hold a death.
     *
     * @return array<int, int> Death count keyed by cell index (gridX * gridSizeY + gridY)
     */
    private function generateZoneCounts(Randomizer $randomizer, int $targetCells, int $gridSizeX, int $gridSizeY): array
    {
        $counts = [];
        for ($hotspot = 0; count($counts) < $targetCells && $hotspot < self::MAX_HOTSPOTS_PER_ZONE; $hotspot++) {
            $centerX = $randomizer->getInt(0, $gridSizeX - 1);
            $centerY = $randomizer->getInt(0, $gridSizeY - 1);
            $sigma   = $randomizer->getInt(self::HOTSPOT_SIGMA_MIN_CELLS, self::HOTSPOT_SIGMA_MAX_CELLS);
            $peak    = $randomizer->getInt(self::HOTSPOT_PEAK_MIN, self::HOTSPOT_PEAK_MAX);
            $reach   = $sigma * 3;

            for ($gridX = max(0, $centerX - $reach); $gridX <= min($gridSizeX - 1, $centerX + $reach); $gridX++) {
                for ($gridY = max(0, $centerY - $reach); $gridY <= min($gridSizeY - 1, $centerY + $reach); $gridY++) {
                    $distanceSquared = ($gridX - $centerX) ** 2 + ($gridY - $centerY) ** 2;
                    $count           = (int)round($peak * exp(-$distanceSquared / (2 * $sigma ** 2)));
                    if ($count < 1) {
                        continue;
                    }

                    $cellIndex          = $gridX * $gridSizeY + $gridY;
                    $counts[$cellIndex] = ($counts[$cellIndex] ?? 0) + $count;
                }
            }
        }

        return $counts;
    }
}
