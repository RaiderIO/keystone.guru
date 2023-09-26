<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced\Versions\V20;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

/**
 * SPELL_CAST_SUCCESS,Player-1084-0A6D63A6,"Sadarøn-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0x10a48,0x0,22568,"Ferocious Bite",0x1,Player-1084-0A6D63A6,0000000000000000,295296,370660,8542,2087,3228,0,3|4,47|5,100|5,25|5,685.25,1257.08,1041,5.7142,407
 *
 * @package App\Logic\CombatLog\CombatEvents\Advanced
 * @author Wouter
 * @since 27/05/2023
 */
class AdvancedDataV20 implements AdvancedDataInterface
{
    private ?Guid $infoGuid;
    private ?Guid $ownerGuid;
    private int   $currentHP;
    private int   $maxHP;
    private int   $attackPower;
    private int   $spellPower;
    private int   $armor;
    private int   $absorb;
    /** @var int[] */
    private array $powerType;
    /** @var int[] */
    private array $currentPower;
    /** @var int[] */
    private array $maxPower;
    /** @var int[] */
    private array $powerCost;
    private float $positionX;
    private float $positionY;
    private int   $uiMapId;
    private float $facing;
    private int   $level;


    /**
     * @return Guid|null
     */
    public function getInfoGuid(): ?Guid
    {
        return $this->infoGuid;
    }

    /**
     * @return Guid|null
     */
    public function getOwnerGuid(): ?Guid
    {
        return $this->ownerGuid;
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
     * @return int[]
     */
    public function getPowerType(): array
    {
        return $this->powerType;
    }

    /**
     * @return int[]
     */
    public function getCurrentPower(): array
    {
        return $this->currentPower;
    }

    /**
     * @return int[]
     */
    public function getMaxPower(): array
    {
        return $this->maxPower;
    }

    /**
     * @return int[]
     */
    public function getPowerCost(): array
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
        $this->infoGuid     = Guid::createFromGuidString($parameters[0]);
        $this->ownerGuid    = Guid::createFromGuidString($parameters[1]);
        $this->currentHP    = $parameters[2];
        $this->maxHP        = $parameters[3];
        $this->attackPower  = $parameters[4];
        $this->spellPower   = $parameters[5];
        $this->armor        = $parameters[6];
        $this->absorb       = $parameters[7];
        $this->powerType    = explode('|', $parameters[8]);
        $this->currentPower = explode('|', $parameters[9]);
        $this->maxPower     = explode('|', $parameters[10]);
        $this->powerCost    = explode('|', $parameters[11]);
        // https://forums.combatlogforums.com/t/unit-positions-from-combat-log-solved/822
        // Be aware also that the coordinates are rotated 90 degrees for some crazy reason. This means that for the two numbers listed, pos1 and pos2, the following rules apply:
        //
        // x-position = -pos2
        // y-position = pos1
        // This fixes the above issue. X and Y are fine after this
        $this->positionX = $parameters[13] * -1;
        $this->positionY = $parameters[12];
        $this->uiMapId   = $parameters[14];
        $this->facing    = $parameters[15];
        $this->level     = $parameters[16];

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
