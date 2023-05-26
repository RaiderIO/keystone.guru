<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class AdvancedData implements HasParameters
{
    private string $infoGUID;
    private string $ownerGUID;
    private int $currentHP;
    private int $maxHP;
    private int $attackPower;
    private int $spellPower;
    private int $armor;
    private int $absorb;
    private int $powerType;
    private int $currentPower;
    private int $maxPower;
    private int $powerCost;
    private float $positionX;
    private float $positionY;
    private int $uiMapId;
    private float $facing;
    private int $level;

    /**
     * @return string
     */
    public function getInfoGUID(): string
    {
        return $this->infoGUID;
    }

    /**
     * @return string
     */
    public function getOwnerGUID(): string
    {
        return $this->ownerGUID;
    }

    /**
     * @return int
     */
    public function getCurrentHP(): int
    {
        return $this->currentHP;
    }

    /**
     * @return int
     */
    public function getMaxHP(): int
    {
        return $this->maxHP;
    }

    /**
     * @return int
     */
    public function getAttackPower(): int
    {
        return $this->attackPower;
    }

    /**
     * @return int
     */
    public function getSpellPower(): int
    {
        return $this->spellPower;
    }

    /**
     * @return int
     */
    public function getArmor(): int
    {
        return $this->armor;
    }

    /**
     * @return int
     */
    public function getAbsorb(): int
    {
        return $this->absorb;
    }

    /**
     * @return int
     */
    public function getPowerType(): int
    {
        return $this->powerType;
    }

    /**
     * @return int
     */
    public function getCurrentPower(): int
    {
        return $this->currentPower;
    }

    /**
     * @return int
     */
    public function getMaxPower(): int
    {
        return $this->maxPower;
    }

    /**
     * @return int
     */
    public function getPowerCost(): int
    {
        return $this->powerCost;
    }

    /**
     * @return float
     */
    public function getPositionX(): float
    {
        return $this->positionX;
    }

    /**
     * @return float
     */
    public function getPositionY(): float
    {
        return $this->positionY;
    }

    /**
     * @return int
     */
    public function getUiMapId(): int
    {
        return $this->uiMapId;
    }

    /**
     * @return float
     */
    public function getFacing(): float
    {
        return $this->facing;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
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

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 17;
    }
}
