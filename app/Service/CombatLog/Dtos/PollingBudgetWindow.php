<?php

namespace App\Service\CombatLog\Dtos;

/**
 * How much of a band's daily polling budget has become available so far today.
 *
 * A band's budget is a daily one, but the whole budget is available from 00:00 onwards, so the
 * hourly poll spends it as fast as it can and then refuses for the rest of the day. That biases
 * every sample towards whichever region happens to be in prime time during those first hours
 * (#4359). This window releases the budget pro rata instead, one slice per opportunity the band
 * actually gets.
 */
readonly class PollingBudgetWindow
{
    /**
     * @param int $elapsedOpportunities The number of hours this band has been scheduled for today,
     *                                  up to and including the current one.
     * @param int $totalOpportunities   The number of hours this band is scheduled for across the
     *                                  whole day.
     */
    public function __construct(
        public int $elapsedOpportunities,
        public int $totalOpportunities,
    ) {
    }

    /**
     * The window that releases the entire daily budget at once, for everything the band rotation
     * does not spread across the day.
     */
    public static function full(): self
    {
        return new self(1, 1);
    }

    /**
     * Whether a count has reached the share of the daily budget released so far.
     *
     * Cross multiplied rather than divided on purpose: at the last opportunity of the day
     * elapsed equals total, so this reduces to `count >= threshold` exactly and the full daily
     * budget is provably reachable - no rounding to argue about.
     */
    public function isAtCeiling(int $count, int $threshold): bool
    {
        return $count * $this->totalOpportunities >= $threshold * $this->elapsedOpportunities;
    }
}
