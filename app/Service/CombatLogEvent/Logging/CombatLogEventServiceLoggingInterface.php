<?php

namespace App\Service\CombatLogEvent\Logging;

use Exception;

interface CombatLogEventServiceLoggingInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function getCombatLogEventsStart(array $filters): void;

    public function getCombatLogEventsException(Exception $e): void;

    public function getCombatLogEventsEnd(): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function getGeotileGridAggregationStart(array $filters): void;

    public function getGeotileGridAggregationException(Exception $e): void;

    public function getGeotileGridAggregationEnd(): void;

    public function getRunCountResult(int $runCount): void;

    public function getRunCountException(Exception $e): void;

    /**
     * @param array<int, int> $runCount
     */
    public function getRunCountPerDungeonResult(array $runCount): void;

    public function getRunCountPerDungeonException(Exception $e): void;

    public function getAvailableDateRangeResult(int $start, int $end): void;

    public function getAvailableDateRangeException(Exception $e): void;
}
