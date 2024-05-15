<?php

namespace App\Service\CombatLogEvent\Logging;

use Exception;

interface CombatLogEventServiceLoggingInterface
{

    public function getCombatLogEventsStart(array $filters): void;

    public function getCombatLogEventsException(Exception $e): void;

    public function getCombatLogEventsEnd(): void;

    public function getGeotileGridAggregationStart(array $filters): void;

    public function getGeotileGridAggregationException(Exception $e): void;

    public function getGeotileGridAggregationEnd(): void;

    public function getRunCountResult(int $runCount): void;

    public function getRunCountException(Exception $e): void;

    public function getRunCountPerDungeonResult(array $runCount): void;

    public function getRunCountPerDungeonException(Exception $e): void;

    public function getAvailableDateRangeResult(int $start, int $end): void;

    public function getAvailableDateRangeException(Exception $e): void;
}
