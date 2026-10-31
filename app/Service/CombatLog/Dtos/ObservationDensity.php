<?php

namespace App\Service\CombatLog\Dtos;

/**
 * How often one fact was observed relative to its subject (a spell or an NPC), over the retained observation history.
 */
readonly class ObservationDensity
{
    /**
     * @param int $factDays          Distinct data-days the fact itself was observed on.
     * @param int $subjectDays       Distinct data-days the subject was observed on with any fact.
     * @param int $recentSubjectDays Distinct data-days, on or after the requested cutoff, the subject was observed on
     *                               with any fact. Zero when no cutoff was requested.
     */
    public function __construct(
        public int $factDays,
        public int $subjectDays,
        public int $recentSubjectDays = 0,
    ) {
    }

    /**
     * The share of the subject's observed days the fact was seen on; 0 for a subject that was never observed.
     */
    public function factRatio(): float
    {
        return $this->subjectDays === 0 ? 0.0 : $this->factDays / $this->subjectDays;
    }
}
