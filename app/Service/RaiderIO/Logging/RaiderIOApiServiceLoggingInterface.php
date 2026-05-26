<?php

namespace App\Service\RaiderIO\Logging;

interface RaiderIOApiServiceLoggingInterface
{
    public function getHeatmapDataStart(string $url): void;

    public function getHeatmapDataInvalidResponse(string $dungeonName, string $url, string $response): void;

    public function getHeatmapDataEnd(): void;

    public function searchAdvancedRunsStart(string $url): void;

    public function searchAdvancedRunsInvalidResponse(string $url, string $response): void;

    public function searchAdvancedRunsEnd(int $count): void;

    public function getCombatLogForRunStart(int $runId): void;

    public function getCombatLogForRunNotConfigured(): void;

    public function getCombatLogForRunInvalidResponse(int $runId, string $url, string $response): void;

    public function getCombatLogForRunEnd(int $runId): void;
}
