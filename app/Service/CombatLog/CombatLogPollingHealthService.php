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

    private const string TOP_BAND_IDLE_STREAK_CACHE_KEY = 'combatlog:pollruns:health:topband:idle-streak';

    /**
     * The idle streak is not bucketed by hour like the other counters: the threshold it feeds is
     * measured in half a day of polls, and hourly buckets expire long before that. A streak that
     * stops being written to at all - the scheduler stopped, the command crashes before the top
     * band - expires instead of alerting forever on a stale count.
     */
    private const int TOP_BAND_IDLE_STREAK_TTL_HOURS = 48;

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

    public function recordTopBandPoll(int $available, int $dispatched): void
    {
        // Available but nothing taken is the signal: a band with nothing available has correctly
        // found nothing, and one that dispatched is working.
        if ($available > 0 && $dispatched === 0) {
            $key = self::TOP_BAND_IDLE_STREAK_CACHE_KEY;

            Cache::add($key, 0, Carbon::now()->addHours(self::TOP_BAND_IDLE_STREAK_TTL_HOURS));
            Cache::increment($key);

            return;
        }

        Cache::forget(self::TOP_BAND_IDLE_STREAK_CACHE_KEY);
    }

    public function getSummary(Carbon $endHour, ?int $windowHours = null): CombatLogPollingHealthSummary
    {
        // A run is counted as dispatched in the hour it was polled, but its outcome lands in the hour
        // the queue got to it - which is a later one whenever the queue is backed up or the job is
        // retried (backoff is 30s and 120s, and the job may take up to its 1800s timeout). A single
        // hour read on its own therefore mixes this hour's dispatches with the previous hour's
        // outcomes, and an hour holding nothing but spillover has no dispatches to measure its
        // failures against at all. Summing a few consecutive hours puts dispatches and their outcomes
        // back in the same window, at the cost of consecutive reports overlapping - which is what we
        // want anyway: an outage that lasts is meant to be reported again (#4173).
        $windowHours ??= (int)config('keystoneguru.raider_io.combat_log_polling.health.window_hours');
        $windowHours = max(1, $windowHours);

        $buckets = [];
        for ($hoursBack = $windowHours - 1; $hoursBack >= 0; $hoursBack--) {
            $buckets[] = $this->getBucket($endHour->copy()->subHours($hoursBack));
        }

        $failuresByReason = [];
        foreach (CombatLogPollingFailureReason::cases() as $reason) {
            $failuresByReason[$reason->value] = $this->readAll($buckets, $reason->value);
        }

        return new CombatLogPollingHealthSummary(
            hour:                        $windowHours === 1 ? $buckets[0] : sprintf('%s..%s', $buckets[0], end($buckets)),
            dispatched:                  $this->readAll($buckets, self::DISPATCHED_COUNTER),
            succeeded:                   $this->readAll($buckets, self::SUCCEEDED_COUNTER),
            failuresByReason:            $failuresByReason,
            // Unlike everything else here this is the streak as it stands now rather than a total
            // over the window - a streak is only meaningful up to the present moment.
            topBandConsecutiveIdlePolls: (int)Cache::get(self::TOP_BAND_IDLE_STREAK_CACHE_KEY, 0),
        );
    }

    public function reportSummary(CombatLogPollingHealthSummary $summary): bool
    {
        $minFailures = (int)config('keystoneguru.raider_io.combat_log_polling.health.min_failures');
        $minRate     = (float)config('keystoneguru.raider_io.combat_log_polling.health.min_failure_rate');

        // Both conditions must hold: the rate alone would page on the two failures of a window that
        // only dispatched two runs, and the count alone would page on a busy window that was fine.
        $degraded = $summary->getTotalFailures() >= $minFailures && $summary->getFailureRate() >= $minRate;

        $idleThreshold = (int)config('keystoneguru.raider_io.combat_log_polling.health.top_band_idle_polls');

        // Reported separately from the failure rate, and below it: the top band going quiet costs
        // the most valuable runs of the season, but every individual poll of it succeeded, so it is
        // not a failure and must not decide whether the window counts as degraded.
        if ($idleThreshold > 0 && $summary->topBandConsecutiveIdlePolls >= $idleThreshold) {
            $this->log->reportTopBandIdle($summary->hour, $summary->topBandConsecutiveIdlePolls, $idleThreshold);
        }

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

    /**
     * @param string[] $buckets
     */
    private function readAll(array $buckets, string $counter): int
    {
        $total = 0;

        foreach ($buckets as $bucket) {
            $total += (int)Cache::get($this->getCacheKey($bucket, $counter), 0);
        }

        return $total;
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
