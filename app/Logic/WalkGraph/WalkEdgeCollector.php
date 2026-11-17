<?php

namespace App\Logic\WalkGraph;

/**
 * Turns per-unit position samples into the set of walk grid edges the units walked, per ui map.
 *
 * Only consecutive samples that look like walking count: blinks, charges, knockbacks, spirit release and
 * gaps in the log would otherwise draw edges through walls.
 */
final class WalkEdgeCollector
{
    /** Below this many yards the millisecond log timestamps make a speed too noisy to judge */
    private const float MIN_SPEED_CHECK_DISTANCE = 3.0;

    /** @var array<string, array{float, float, float, int}> unit => [timestamp, x, y, uiMapId] */
    private array $lastSamples = [];

    /** @var array<int, array<int, true>> uiMapId => edge key => true */
    private array $edges = [];

    public function __construct(
        private readonly float $cellSize,
        private readonly float $maxStepSeconds,
        private readonly float $maxStepDistance,
        private readonly float $maxSpeed,
    ) {
    }

    /**
     * @param float $timestamp Seconds.
     */
    public function addSample(string $unit, int $uiMapId, float $timestamp, float $x, float $y): void
    {
        $lastSample = $this->lastSamples[$unit] ?? null;
        if ($lastSample !== null && $lastSample[1] === $x && $lastSample[2] === $y && $lastSample[3] === $uiMapId) {
            // Standing still: the next step is timed from the moment the unit was last seen here
            $this->lastSamples[$unit][0] = $timestamp;

            return;
        }

        $this->lastSamples[$unit] = [$timestamp, $x, $y, $uiMapId];

        if ($lastSample === null || $lastSample[3] !== $uiMapId) {
            return;
        }

        $seconds = $timestamp - $lastSample[0];
        if ($seconds <= 0 || $seconds > $this->maxStepSeconds) {
            return;
        }

        $distance = hypot($x - $lastSample[1], $y - $lastSample[2]);
        if ($distance > $this->maxStepDistance ||
            ($distance > self::MIN_SPEED_CHECK_DISTANCE && $distance / $seconds > $this->maxSpeed)) {
            return;
        }

        $fromCellX = WalkGrid::cellCoordinate($lastSample[1], $this->cellSize);
        $fromCellY = WalkGrid::cellCoordinate($lastSample[2], $this->cellSize);
        $toCellX   = WalkGrid::cellCoordinate($x, $this->cellSize);
        $toCellY   = WalkGrid::cellCoordinate($y, $this->cellSize);
        if ($fromCellX === $toCellX && $fromCellY === $toCellY) {
            return;
        }

        $cells = WalkGrid::rasterize($fromCellX, $fromCellY, $toCellX, $toCellY);
        for ($i = 1, $count = count($cells); $i < $count; $i++) {
            $edgeKey = WalkGrid::edgeKey($cells[$i - 1][0], $cells[$i - 1][1], $cells[$i][0], $cells[$i][1]);
            if ($edgeKey !== null) {
                $this->edges[$uiMapId][$edgeKey] = true;
            }
        }
    }

    /**
     * @return array<int, array<int, int>> uiMapId => edge keys
     */
    public function getEdgeKeysByUiMapId(): array
    {
        return array_map(static fn(array $edges) => array_keys($edges), $this->edges);
    }

    public function reset(): void
    {
        $this->lastSamples = [];
        $this->edges       = [];
    }
}
