<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class AdvancedData implements HasParameters
{
    public string $infoGUID;
    public string $ownerGUID;
    public int $currentHP;
    public int $maxHP;
    public int $attackPower;
    public int $spellPower;
    public int $armor;
    public int $absorb;
    public int $powerType;
    public int $currentPower;
    public int $maxPower;
    public int $powerCost;
    public float $positionX;
    public float $positionY;
    public int $uiMapId;
    public float $facing;
    public int $level;

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 17;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        $this->infoGUID     = $parameters[0];
        $this->ownerGUID    = $parameters[1];
        $this->currentHP    = $parameters[2];
        $this->maxHP        = $parameters[3];
        $this->attackPower  = $parameters[4];
        $this->spellPower   = $parameters[5];
        $this->armor        = $parameters[6];
        $this->absorb       = $parameters[7];
        $this->powerType    = $parameters[8];
        $this->currentPower = $parameters[9];
        $this->maxPower     = $parameters[10];
        $this->powerCost    = $parameters[11];
        $this->positionX    = $parameters[12];
        $this->positionY    = $parameters[13];
        $this->uiMapId      = $parameters[14];
        $this->facing       = $parameters[15];
        $this->level        = $parameters[16];
    }
}
