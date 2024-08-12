<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V20;

use App\Logic\CombatLog\SpecialEvents\EncounterStart\EncounterStartInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * ENCOUNTER_START,2111,"Elder Leaxa",8,5,1841
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class EncounterStartV20 extends SpecialEvent implements EncounterStartInterface
{
    private int $instanceID;

    public function getInstanceID(): int
    {
        return $this->instanceID;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->instanceID = $parameters[4];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 5;
    }
}
