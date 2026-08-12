<?php

namespace App\Service\CombatLog;

use App\Models\Season;
use App\Service\CombatLog\Dtos\KeyLevelBand;

interface CombatLogPollingBandServiceInterface
{
    /**
     * Returns the highest keystone level that is being played in any meaningful volume this
     * season, determined by probing Raider.IO and cached for the remainder of the ISO week.
     *
     * "Meaningful volume" is deliberately not "any run at all": a handful of record attempts
     * worldwide would otherwise drag the top band up and shrink it to nothing.
     */
    public function getMaxKeyLevel(Season $season): int;

    /**
     * Returns the open ended band of the highest keys of the season. Runs in this band are always
     * parsed and never consume a band budget.
     */
    public function getTopBand(Season $season): KeyLevelBand;

    /**
     * Returns the budgeted bands, covering everything from the configured minimum level up to
     * (but excluding) the top band.
     *
     * @return list<KeyLevelBand>
     */
    public function getSpreadBands(Season $season): array;

    /**
     * Returns the single spread band to poll during the given hour of the day. Polling every band
     * every hour would multiply the number of Raider.IO calls by the band count, so the bands take
     * turns instead. Null when there are no spread bands at all.
     */
    public function getSpreadBandForHour(Season $season, int $hour): ?KeyLevelBand;
}
