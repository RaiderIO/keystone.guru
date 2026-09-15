<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9SoD;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use App\Logic\CombatLog\Guid\Guid;

/**
 * 12/9/2024 18:51:23.3640  SPELL_CAST_SUCCESS,Player-5827-01FF533F,"Nikp-LivingFlame-EU",0x511,0x0,0000000000000000,nil,0x80000000,0x80000000,425600,"Horn of Lordaeron",0x1,Player-5827-01FF533F,0000000000000000,100,100,0,0,0,0,0,-1,0,0,0,-8213.53,2012.04,0,1.3086,72
 *
 * Cheap scalar fields are assigned eagerly in {@see setParameters()}; only the expensive fields
 * (GUID parsing, power-array splitting) are parsed lazily, each on its own first getter access.
 *
 * @author Wouter
 *
 * @since 08/01/2025
 */
class AdvancedDataV9SoD implements AdvancedDataInterface
{
    use ValidatesParameterCount;

    private string $infoGuidRaw = '0000000000000000';

    private string $ownerGuidRaw = '0000000000000000';

    /** false = not parsed yet ({@see Guid::createFromGuidString()} may legitimately return null) */
    private Guid|null|false $infoGuid = false;

    /** false = not parsed yet ({@see Guid::createFromGuidString()} may legitimately return null) */
    private Guid|null|false $ownerGuid = false;

    private int $currentHP;

    private int $maxHP;

    private int $power;

    private int $armor;

    private int $unknown1;

    private int $unknown2;

    private int $absorb;

    private string $powerTypeRaw;

    private string $currentPowerRaw;

    private string $maxPowerRaw;

    private string $powerCostRaw;

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

    private int $uiMapId;

    private float $facing;

    private int $level;

    public function getInfoGuidRaw(): string
    {
        return $this->infoGuidRaw;
    }

    public function getInfoGuid(): ?Guid
    {
        if ($this->infoGuid === false) {
            $this->infoGuid = Guid::createFromGuidString($this->infoGuidRaw);
        }

        return $this->infoGuid;
    }

    public function getOwnerGuid(): ?Guid
    {
        if ($this->ownerGuid === false) {
            $this->ownerGuid = Guid::createFromGuidString($this->ownerGuidRaw);
        }

        return $this->ownerGuid;
    }

    public function getCurrentHP(): int
    {
        return $this->currentHP;
    }

    public function getMaxHP(): int
    {
        return $this->maxHP;
    }

    public function getAttackPower(): int
    {
        return $this->power;
    }

    public function getSpellPower(): int
    {
        return $this->power;
    }

    public function getArmor(): int
    {
        return $this->armor;
    }

    public function getUnknown1(): int
    {
        return $this->unknown1;
    }

    public function getUnknown2(): int
    {
        return $this->unknown2;
    }

    public function getAbsorb(): int
    {
        return $this->absorb;
    }

    /**
     * @return int[]
     */
    public function getPowerType(): array
    {
        if (!isset($this->powerType)) {
            $this->powerType = array_map(intval(...), explode('|', $this->powerTypeRaw));
        }

        return $this->powerType;
    }

    /**
     * @return int[]
     */
    public function getCurrentPower(): array
    {
        if (!isset($this->currentPower)) {
            $this->currentPower = array_map(intval(...), explode('|', $this->currentPowerRaw));
        }

        return $this->currentPower;
    }

    /**
     * @return int[]
     */
    public function getMaxPower(): array
    {
        if (!isset($this->maxPower)) {
            $this->maxPower = array_map(intval(...), explode('|', $this->maxPowerRaw));
        }

        return $this->maxPower;
    }

    /**
     * @return int[]
     */
    public function getPowerCost(): array
    {
        if (!isset($this->powerCost)) {
            $this->powerCost = array_map(intval(...), explode('|', $this->powerCostRaw));
        }

        return $this->powerCost;
    }

    public function getPositionX(): float
    {
        return $this->positionX;
    }

    public function getPositionY(): float
    {
        return $this->positionY;
    }

    public function getUiMapId(): int
    {
        return $this->uiMapId;
    }

    public function getFacing(): float
    {
        return $this->facing;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setParameters(array $parameters): HasParameters
    {
        $this->validateParameters($parameters);

        $this->infoGuidRaw     = (string)$parameters[0];
        $this->ownerGuidRaw    = (string)$parameters[1];
        $this->currentHP       = $parameters[2];
        $this->maxHP           = $parameters[3];
        $this->power           = $parameters[4];
        $this->armor           = $parameters[5];
        $this->unknown1        = $parameters[6];
        $this->unknown2        = $parameters[7];
        $this->absorb          = $parameters[8];
        $this->powerTypeRaw    = (string)$parameters[9];
        $this->currentPowerRaw = (string)$parameters[10];
        $this->maxPowerRaw     = (string)$parameters[11];
        $this->powerCostRaw    = (string)$parameters[12];
        // https://forums.combatlogforums.com/t/unit-positions-from-combat-log-solved/822
        // Be aware also that the coordinates are rotated 90 degrees for some crazy reason. This means that for the two numbers listed, pos1 and pos2, the following rules apply:
        //
        // x-position = -pos2
        // y-position = pos1
        // This fixes the above issue. X and Y are fine after this
        $this->positionX = $parameters[14] * -1;
        $this->positionY = $parameters[13];
        $this->uiMapId   = $parameters[15];
        $this->facing    = $parameters[16];
        $this->level     = $parameters[17];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 18;
    }
}
