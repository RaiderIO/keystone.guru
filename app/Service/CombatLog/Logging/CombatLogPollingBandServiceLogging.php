<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogPollingBandServiceLogging extends StructuredLogging implements CombatLogPollingBandServiceLoggingInterface
{
    use InteractsWithRollbar;

    /**
     * The probe has a fallback path (the last known max key level, or the probe ceiling), so this
     * is a warning and not an error - it must not page anyone through Discord.
     *
     * The $exceptionClass and $message parameter names are load-bearing: FingerprintsStructuredErrorsHandler
     * keys on those literal context keys, so renaming them silently changes Sentry grouping.
     */
    public function getMaxKeyLevelProbeFailed(int $seasonId, ?int $lastKnown, string $exceptionClass, string $message): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getMaxKeyLevelProbed(int $seasonId, int $maxKeyLevel, int $cacheMinutes): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
