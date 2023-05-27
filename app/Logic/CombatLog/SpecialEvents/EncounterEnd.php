<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * ENCOUNTER_START,2111,"Elder Leaxa",8,5,1841
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class EncounterEnd extends EncounterBase
{
    private int $success;

    private int $fightTimeMS;

    /**
     * @return int
     */
    public function getSuccess(): int
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getFightTimeMS(): int
    {
        return $this->fightTimeMS;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->success     = $parameters[4];
        $this->fightTimeMS = $parameters[5];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 6;
    }
}
