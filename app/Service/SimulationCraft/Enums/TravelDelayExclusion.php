<?php

namespace App\Service\SimulationCraft\Enums;

/**
 * Why an observed gap between two pulls says nothing about travel time, and is left out of the calibration.
 */
enum TravelDelayExclusion: string
{
    /** The pull was engaged before the previous one had died - a chain pull, there was no travel in between. */
    case Overlap = 'overlap';

    /** A player died during the previous pull or the gap, so the gap holds a release/run back or a battle res. */
    case PlayerDeath = 'player_death';
}
