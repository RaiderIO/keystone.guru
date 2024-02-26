<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

/**
 * SPELL_CAST_SUCCESS,Player-4904-00BE51D0,"Laronia-ClassicPTRRealm1",0x511,0x0,0000000000000000,nil,0x80000000,0x80000000,768,"Cat Form",0x1,Player-4904-00BE51D0,0000000000000000,59,100,4996,104,4644,0,6032,7311,1223,-339.18,94.39,220,3.3890,200
 *
 * @package App\Logic\CombatLog\CombatEvents\Advanced
 * @author Wouter
 * @since 27/05/2023
 */
class AdvancedDataV9 implements AdvancedDataInterface
{
    private ?Guid $infoGuid  = null;
    private ?Guid $ownerGuid = null;
    private int   $currentHP;
    private int   $maxHP;
    private int   $power;
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
        return $this->power;
    }

    /**
     * @return int
     */
    public function getSpellPower(): int
    {
        return $this->power;
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
        $this->power        = $parameters[4];
        $this->armor        = $parameters[5];
        $this->absorb       = $parameters[6];
        $this->powerType    = explode('|', (string) $parameters[7]);
        $this->currentPower = explode('|', (string) $parameters[8]);
        $this->maxPower     = explode('|', (string) $parameters[9]);
        $this->powerCost    = explode('|', (string) $parameters[10]);
        // https://forums.combatlogforums.com/t/unit-positions-from-combat-log-solved/822
        // Be aware also that the coordinates are rotated 90 degrees for some crazy reason. This means that for the two numbers listed, pos1 and pos2, the following rules apply:
        //
        // x-position = -pos2
        // y-position = pos1
        // This fixes the above issue. X and Y are fine after this
        $this->positionX = $parameters[12] * -1;
        $this->positionY = $parameters[11];
        $this->uiMapId   = $parameters[13];
        $this->facing    = $parameters[14];
        $this->level     = $parameters[15];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 16;
    }
}
