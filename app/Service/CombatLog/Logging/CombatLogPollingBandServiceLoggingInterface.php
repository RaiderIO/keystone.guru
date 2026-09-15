<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogPollingBandServiceLoggingInterface
{
    public function getMaxKeyLevelProbeFailed(int $seasonId, ?int $lastKnown, string $exceptionClass, string $message): void;

    public function getMaxKeyLevelProbed(int $seasonId, int $maxKeyLevel, int $cacheMinutes): void;
}
