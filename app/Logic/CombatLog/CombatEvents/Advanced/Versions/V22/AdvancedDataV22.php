<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced\Versions\V22;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use App\Logic\CombatLog\Guid\Guid;

/**
 * 11/9/2024 23:12:35.9931  SWING_DAMAGE,Player-1403-09A74524,\"Llewéllyn-Draenor-EU\",0x512,0x20,Creature-0-4251-2662-13168-213892-00002FDE4F,\"Nightfall Shadowmage\",0xa48,0x1,Player-1403-09A74524,0000000000000000,7826760,7826760,83049,18722,108464,1180,145,0,0,2500000,2500000,0,1433.26,-245.27,2359,5.4636,615,28011,40015,-1,1,0,0,0,nil,nil,nil
 *
 * @author Wouter
 *
 * @since 10/12/2024
 */
class AdvancedDataV22 implements AdvancedDataInterface
{
    use ValidatesParameterCount;

    private string $infoGuidRaw = '0000000000000000';

    /** @var array<int, mixed>|null Retained until first real getter access, then materialized and discarded. */
    private ?array $parameters = null;

    private ?Guid $infoGuid = null;

    private ?Guid $ownerGuid = null;

    private int $currentHP;

    private int $maxHP;

    private int $attackPower;

    private int $spellPower;

    private int $armor;

    private int $unknown1;

    private int $unknown2;

    private int $absorb;

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
        $this->materialize();

        return $this->infoGuid;
    }

    public function getOwnerGuid(): ?Guid
    {
        $this->materialize();

        return $this->ownerGuid;
    }

    public function getCurrentHP(): int
    {
        $this->materialize();

        return $this->currentHP;
    }

    public function getMaxHP(): int
    {
        $this->materialize();

        return $this->maxHP;
    }

    public function getAttackPower(): int
    {
        $this->materialize();

        return $this->attackPower;
    }

    public function getSpellPower(): int
    {
        $this->materialize();

        return $this->spellPower;
    }

    public function getArmor(): int
    {
        $this->materialize();

        return $this->armor;
    }

    public function getUnknown1(): int
    {
        $this->materialize();

        return $this->unknown1;
    }

    public function getUnknown2(): int
    {
        $this->materialize();

        return $this->unknown2;
    }

    public function getAbsorb(): int
    {
        $this->materialize();

        return $this->absorb;
    }

    /**
     * @return int[]
     */
    public function getPowerType(): array
    {
        $this->materialize();

        return $this->powerType;
    }

    /**
     * @return int[]
     */
    public function getCurrentPower(): array
    {
        $this->materialize();

        return $this->currentPower;
    }

    /**
     * @return int[]
     */
    public function getMaxPower(): array
    {
        $this->materialize();

        return $this->maxPower;
    }

    /**
     * @return int[]
     */
    public function getPowerCost(): array
    {
        $this->materialize();

        return $this->powerCost;
    }

    public function getPositionX(): float
    {
        $this->materialize();

        return $this->positionX;
    }

    public function getPositionY(): float
    {
        $this->materialize();

        return $this->positionY;
    }

    public function getUiMapId(): int
    {
        $this->materialize();

        return $this->uiMapId;
    }

    public function getFacing(): float
    {
        $this->materialize();

        return $this->facing;
    }

    public function getLevel(): int
    {
        $this->materialize();

        return $this->level;
    }

    public function setParameters(array $parameters): HasParameters
    {
        $this->validateParameters($parameters);

        $this->infoGuidRaw = (string)$parameters[0];
        $this->parameters  = $parameters;

        return $this;
    }

    private function materialize(): void
    {
        if ($this->parameters === null) {
            return;
        }

        $parameters       = $this->parameters;
        $this->parameters = null;

        $this->infoGuid     = Guid::createFromGuidString($parameters[0]);
        $this->ownerGuid    = Guid::createFromGuidString($parameters[1]);
        $this->currentHP    = $parameters[2];
        $this->maxHP        = $parameters[3];
        $this->attackPower  = $parameters[4];
        $this->spellPower   = $parameters[5];
        $this->armor        = $parameters[6];
        $this->unknown1     = $parameters[7];
        $this->unknown2     = $parameters[8];
        $this->absorb       = $parameters[9];
        $this->powerType    = array_map(intval(...), explode('|', (string)$parameters[10]));
        $this->currentPower = array_map(intval(...), explode('|', (string)$parameters[11]));
        $this->maxPower     = array_map(intval(...), explode('|', (string)$parameters[12]));
        $this->powerCost    = array_map(intval(...), explode('|', (string)$parameters[13]));
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
    }

    public function getParameterCount(): int
    {
        return 19;
    }
}
