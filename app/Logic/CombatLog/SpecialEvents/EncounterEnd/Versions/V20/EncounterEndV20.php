<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V20;

use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class EncounterEndV20 extends SpecialEvent implements EncounterEndInterface
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
