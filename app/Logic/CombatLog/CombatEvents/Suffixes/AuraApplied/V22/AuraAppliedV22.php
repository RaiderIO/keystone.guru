<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22;

use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;

/**
 * Two versions!! One where there's an immunity of sorts, and one where there's not.
 *
 * Line    695: 11/9/2024 23:12:43.2561  SPELL_AuraApplied,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Creature-0-4251-2662-13168-214761-00002FDE4F,"Nightfall Ritualist",0xa48,0x80,30283,"Shadowfury",0x20,IMMUNE,nil,AOE
 * Line   4848: 11/9/2024 23:13:04.1031  SPELL_AuraApplied,Creature-0-4251-2662-13168-213894-0000AFDE4F,"Nightfall Curseblade",0xa48,0x0,Player-1403-09A74524,"Llewéllyn-Draenor-EU",0x512,0x20,431493,"Darkblade",0x20,PARRY,nil,ST
 * Line   6528: 11/9/2024 23:13:32.8891  SPELL_AuraApplied,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1403-09A74524,"Llewéllyn-Draenor-EU",0x512,0x20,431491,"Tainted Slash",0x1,MISS,nil,ST
 * Line   7380: 11/9/2024 23:13:39.5971  SPELL_AuraApplied,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1096-0AC6D32F,"Andy-Ravenholdt-EU",0x512,0x0,450756,"Abyssal Howl",0x20,ABSORB,nil,559824,745709,nil,AOE
 */
class AuraAppliedV22 extends AuraBase implements AuraAppliedInterface
{

    public function getUnknown(): ?int
    {
        return null;
    }
}
