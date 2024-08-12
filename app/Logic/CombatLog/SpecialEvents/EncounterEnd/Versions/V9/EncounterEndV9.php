<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V9;

use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * ENCOUNTER_END,665,"Gehennas",226,20,1
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class EncounterEndV9 extends SpecialEvent implements EncounterEndInterface
{

    private int $success;

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function getFightTimeMS(): int
    {
        return 0;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->success     = $parameters[4];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 5;
    }
}
