<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V22;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\MissType\Block;
use App\Logic\CombatLog\Guid\MissType\Resist;

/**
 * Two versions!! One where there's an immunity of sorts, and one where there's not.
 *
 * Line    695: 11/9/2024 23:12:43.2561  SPELL_MISSED,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Creature-0-4251-2662-13168-214761-00002FDE4F,"Nightfall Ritualist",0xa48,0x80,30283,"Shadowfury",0x20,IMMUNE,nil,AOE
 * Line   4848: 11/9/2024 23:13:04.1031  SPELL_MISSED,Creature-0-4251-2662-13168-213894-0000AFDE4F,"Nightfall Curseblade",0xa48,0x0,Player-1403-09A74524,"Llewéllyn-Draenor-EU",0x512,0x20,431493,"Darkblade",0x20,PARRY,nil,ST
 * Line   6528: 11/9/2024 23:13:32.8891  SPELL_MISSED,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1403-09A74524,"Llewéllyn-Draenor-EU",0x512,0x20,431491,"Tainted Slash",0x1,MISS,nil,ST
 * Line   7380: 11/9/2024 23:13:39.5971  SPELL_MISSED,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1096-0AC6D32F,"Andy-Ravenholdt-EU",0x512,0x0,450756,"Abyssal Howl",0x20,ABSORB,nil,559824,745709,nil,AOE
 */
class MissedV22 extends Suffix implements MissedInterface
{
    private Guid $missType;

    private bool $offHand;

    private int $amountMissed;

    private int $amountTotal;

    private bool $critical;

    private ?string $damageType = null;

    public function getMissType(): Guid
    {
        return $this->missType;
    }

    public function isOffHand(): bool
    {
        return $this->offHand;
    }

    public function getAmountMissed(): int
    {
        return $this->amountMissed;
    }

    public function getAmountTotal(): int
    {
        return $this->amountTotal;
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function getDamageType(): ?string
    {
        return $this->damageType;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->missType = Guid::createFromGuidString($parameters[0]);
        $this->offHand  = $parameters[1] !== 'nil';

        // It was an immune of sorts apparently. But the parameter CAN be optional if it's a SWING instead of a SPELL..
        if (!isset($parameters[2]) || in_array($parameters[2], [
            'ST',
            'AOE',
        ])) {
            $this->amountMissed = 0;
            $this->amountTotal  = 0;
            $this->critical     = false;
            $this->damageType   = $parameters[2] ?? null;
        } elseif ($this->missType instanceof Block || $this->missType instanceof Resist) {
            $this->amountMissed = $parameters[2];
            $this->amountTotal  = 0;
            $this->critical     = false;
            $this->damageType   = $parameters[3] ?? null;
        } else {
            $this->amountMissed = $parameters[2];
            $this->amountTotal  = $parameters[3];
            $this->critical     = $parameters[4] !== 'nil';
            $this->damageType   = $parameters[5] ?? null;
        }

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 4;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
