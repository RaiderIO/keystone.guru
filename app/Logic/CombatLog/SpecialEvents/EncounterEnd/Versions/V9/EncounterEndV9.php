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

    private int    $encounterId;
    private string $encounterName;
    private int    $difficultyId;
    private int    $groupSize;
    private int    $success;

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
        return 0;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->encounterId   = $parameters[0];
        $this->encounterName = $parameters[1];
        $this->difficultyId  = $parameters[2];
        $this->groupSize     = $parameters[3];
        $this->success       = $parameters[4];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 5;
    }
}
