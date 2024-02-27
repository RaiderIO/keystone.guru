<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * ENCOUNTER_START,2111,"Elder Leaxa",8,5,1841
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class EncounterEnd extends EncounterBase
{
    private int $success;

    private int $fightTimeMS;

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function getFightTimeMS(): int
    {
        return $this->fightTimeMS;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->success     = $parameters[4];
        $this->fightTimeMS = $parameters[5];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
