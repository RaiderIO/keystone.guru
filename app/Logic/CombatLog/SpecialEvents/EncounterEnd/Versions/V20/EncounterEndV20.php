<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V20;

use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * 6/27 21:37:42.817  ENCOUNTER_END,2100,"Viq'Goth",8,5,1,227927
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class EncounterEndV20 extends SpecialEvent implements EncounterEndInterface
{
    private int $encounterId;
    private string $encounterName;
    private int $difficultyId;
    private int $groupSize;
    private int $success;
    private int $fightTimeMS;

    public function getEncounterId(): int
    {
        return $this->encounterId;
    }

    public function getEncounterName(): string
    {
        return $this->encounterName;
    }

    public function getDifficultyId(): int
    {
        return $this->difficultyId;
    }

    public function getGroupSize(): int
    {
        return $this->groupSize;
    }

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

        $this->encounterId   = $parameters[0];
        $this->encounterName = $parameters[1];
        $this->difficultyId  = $parameters[2];
        $this->groupSize     = $parameters[3];
        $this->success       = $parameters[4];
        $this->fightTimeMS   = $parameters[5];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
