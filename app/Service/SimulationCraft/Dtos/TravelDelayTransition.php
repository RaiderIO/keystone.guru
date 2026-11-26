<?php

namespace App\Service\SimulationCraft\Dtos;

use App\Service\SimulationCraft\Enums\TravelDelayExclusion;

/**
 * One pull-to-pull transition of a real run: the travel delay SimulationCraft is given for it, next to the time the
 * group actually took from the last death of the previous pull to the first engage of this one.
 */
readonly class TravelDelayTransition
{
    /**
     * @param string                    $dungeonKey       The dungeon the run was in.
     * @param int                       $pullIndex        The index of the pull travelled towards; the previous pull is the one before it.
     * @param int                       $predictedSeconds The delay RaidEventPull calculates for this pull.
     * @param float                     $observedSeconds  The seconds between the previous pull's last death and this pull's first engage.
     * @param TravelDelayExclusion|null $exclusion        Why this transition is left out of the calibration, if it is.
     */
    public function __construct(
        public string                $dungeonKey,
        public int                   $pullIndex,
        public int                   $predictedSeconds,
        public float                 $observedSeconds,
        public ?TravelDelayExclusion $exclusion = null,
    ) {
    }

    public function isIncluded(): bool
    {
        return $this->exclusion === null;
    }
}
