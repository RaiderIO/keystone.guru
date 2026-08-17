<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced\Versions\V22;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use App\Logic\CombatLog\Guid\Guid;

/**
 * 11/9/2024 23:12:35.9931  SWING_DAMAGE,Player-1403-09A74524,\"Llewéllyn-Draenor-EU\",0x512,0x20,Creature-0-4251-2662-13168-213892-00002FDE4F,\"Nightfall Shadowmage\",0xa48,0x1,Player-1403-09A74524,0000000000000000,7826760,7826760,83049,18722,108464,1180,145,0,0,2500000,2500000,0,1433.26,-245.27,2359,5.4636,615,28011,40015,-1,1,0,0,0,nil,nil,nil
 *
 * Cheap scalar fields are assigned eagerly in {@see setParameters()}; only the expensive fields
 * (GUID parsing, power-array splitting) are parsed lazily, each on its own first getter access.
 *
 * @author Wouter
 *
 * @since 10/12/2024
 */
class AdvancedDataV22 implements AdvancedDataInterface
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

    private int $attackPower;

    private int $spellPower;

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
        return $this->attackPower;
    }

    public function getSpellPower(): int
    {
        return $this->spellPower;
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
        $this->attackPower     = $parameters[4];
        $this->spellPower      = $parameters[5];
        $this->armor           = $parameters[6];
        $this->unknown1        = $parameters[7];
        $this->unknown2        = $parameters[8];
        $this->absorb          = $parameters[9];
        $this->powerTypeRaw    = (string)$parameters[10];
        $this->currentPowerRaw = (string)$parameters[11];
        $this->maxPowerRaw     = (string)$parameters[12];
        $this->powerCostRaw    = (string)$parameters[13];
        // https://forums.combatlogforums.com/t/unit-positions-from-combat-log-solved/822
        // Be aware also that the coordinates are rotated 90 degrees for some crazy reason. This means that for the two numbers listed, pos1 and pos2, the following rules apply:
        //
        // x-position = -pos2
        // y-position = pos1
        // This fixes the above issue. X and Y are fine after this
        $this->positionX = $parameters[15] * -1;
        $this->positionY = $parameters[14];
        // @TODO TEMP ARA-KARA FIX
        $this->uiMapId = $parameters[16];
        $this->facing  = $parameters[17];
        $this->level   = $parameters[18];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 19;
    }
}
