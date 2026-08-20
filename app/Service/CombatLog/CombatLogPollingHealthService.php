<?php

namespace App\Service\CombatLog;

use App\Service\CombatLog\Dtos\CombatLogPollingHealthSummary;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
use App\Service\CombatLog\Logging\CombatLogPollingHealthServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CombatLogPollingHealthService implements CombatLogPollingHealthServiceInterface
{
    private const string COUNTER_CACHE_KEY = 'combatlog:pollruns:health:%s:%s';

    private const string DISPATCHED_COUNTER = 'dispatched';

    private const string SUCCEEDED_COUNTER = 'succeeded';

    /**
     * Long enough that the hourly report always finds the hour it reports on intact - even if the
     * scheduler is late or a run of it is skipped - and short enough that nothing lingers.
     */
    private const int COUNTER_TTL_HOURS = 6;

    public function __construct(
        private readonly CombatLogPollingHealthServiceLoggingInterface $log,
    ) {
    }

    public function recordDispatched(): void
    {
        $this->increment(self::DISPATCHED_COUNTER);
    }

    public function recordSucceeded(): void
    {
        $this->increment(self::SUCCEEDED_COUNTER);
    }

    public function recordFailure(CombatLogPollingFailureReason $reason): void
    {
        $this->increment($reason->value);
    }

    public function getSummary(Carbon $hour): CombatLogPollingHealthSummary
    {
        $bucket = $this->getBucket($hour);

        $failuresByReason = [];
        foreach (CombatLogPollingFailureReason::cases() as $reason) {
            $failuresByReason[$reason->value] = $this->read($bucket, $reason->value);
        }

        return new CombatLogPollingHealthSummary(
            hour:             $bucket,
            dispatched:       $this->read($bucket, self::DISPATCHED_COUNTER),
            succeeded:        $this->read($bucket, self::SUCCEEDED_COUNTER),
            failuresByReason: $failuresByReason,
        );
    }

    public function reportSummary(CombatLogPollingHealthSummary $summary): bool
    {
        $minFailures = (int)config('keystoneguru.raider_io.combat_log_polling.health.min_failures');
        $minRate     = (float)config('keystoneguru.raider_io.combat_log_polling.health.min_failure_rate');

        // Both conditions must hold: the rate alone would page on the two failures of an hour that
        // only dispatched two runs, and the count alone would page on a busy hour that was fine.
        $degraded = $summary->getTotalFailures() >= $minFailures && $summary->getFailureRate() >= $minRate;

        if ($degraded) {
            $this->log->reportSummaryDegraded(
                $summary->hour,
                $summary->dispatched,
                $summary->succeeded,
                $summary->getTotalFailures(),
                $summary->getFailureRate(),
                $summary->failuresByReason,
            );
        } else {
            $this->log->reportSummaryHealthy(
                $summary->hour,
                $summary->dispatched,
                $summary->succeeded,
                $summary->getTotalFailures(),
                $summary->getFailureRate(),
                $summary->failuresByReason,
            );
        }

        return $degraded;
    }

    private function increment(string $counter): void
    {
        $key = $this->getCacheKey($this->getBucket(Carbon::now()), $counter);

        // add() first so the counter carries a TTL: increment() on a missing key creates it without
        // one on the Redis store, and the bucket would then live until the cache prefix rotates.
        Cache::add($key, 0, Carbon::now()->addHours(self::COUNTER_TTL_HOURS));
        Cache::increment($key);
    }

    private function read(string $bucket, string $counter): int
    {
        return (int)Cache::get($this->getCacheKey($bucket, $counter), 0);
    }

    private function getBucket(Carbon $moment): string
    {
        return $moment->format('Y-m-d-H');
    }

    private function getCacheKey(string $bucket, string $counter): string
    {
        return sprintf(self::COUNTER_CACHE_KEY, $bucket, $counter);
    }
}
