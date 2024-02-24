<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * ENCOUNTER_START,2111,"Elder Leaxa",8,5,1841
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class EncounterStart extends EncounterBase
{
    private int $instanceID;

    /**
     * @return int
     */
    public function getInstanceID(): int
    {
        return $this->instanceID;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->instanceID = $parameters[4];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 5;
    }
}
