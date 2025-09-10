<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V9;

use App\Logic\CombatLog\SpecialEvents\EncounterStart\EncounterStartInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

/**
 * ENCOUNTER_START,665,"Gehennas",226,20,409,2
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class EncounterStartV9 extends SpecialEvent implements EncounterStartInterface
{
    private int $encounterId;
    private string $encounterName;
    private int $difficultyId;
    private int $groupSize;
    private int $instanceID;

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

    public function getInstanceID(): int
    {
        return $this->instanceID;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->encounterId   = $parameters[0];
        $this->encounterName = $parameters[1];
        $this->difficultyId  = $parameters[2];
        $this->groupSize     = $parameters[3];
        $this->instanceID    = $parameters[4];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
