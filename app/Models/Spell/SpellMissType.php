<?php

namespace App\Models\Spell;

use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\MissType\Absorb;
use App\Logic\CombatLog\Guid\MissType\Block;
use App\Logic\CombatLog\Guid\MissType\Deflect;
use App\Logic\CombatLog\Guid\MissType\Dodge;
use App\Logic\CombatLog\Guid\MissType\Evade;
use App\Logic\CombatLog\Guid\MissType\Immune;
use App\Logic\CombatLog\Guid\MissType\Miss;
use App\Logic\CombatLog\Guid\MissType\Parry;
use App\Logic\CombatLog\Guid\MissType\Reflect;
use App\Logic\CombatLog\Guid\MissType\Resist;
use App\Models\Traits\BitmaskEnum;

/**
 * The ways a spell has been seen to miss its target, one bit each on `spells`.`miss_types_mask`.
 */
enum SpellMissType: int
{
    use BitmaskEnum;

    case Absorb    = 1;
    case Block     = 2;
    case Deflect   = 4;
    case Dodge     = 8;
    case Evade     = 16;
    case Immune    = 32;
    case Miss      = 64;
    case Parry     = 128;
    case Reflect   = 256;
    case Resist    = 512;
    case Interrupt = 1024;

    /**
     * The miss type a combat log `SPELL_MISSED` line's miss type GUID stands for. Interrupt has no miss type GUID,
     * so it is never returned; neither is anything for a GUID that is not a miss type.
     */
    public static function tryFromGuid(Guid $missType): ?self
    {
        return match ($missType::class) {
            Absorb::class  => self::Absorb,
            Block::class   => self::Block,
            Deflect::class => self::Deflect,
            Dodge::class   => self::Dodge,
            Evade::class   => self::Evade,
            Immune::class  => self::Immune,
            Miss::class    => self::Miss,
            Parry::class   => self::Parry,
            Reflect::class => self::Reflect,
            Resist::class  => self::Resist,
            default        => null,
        };
    }

    /**
     * The slug of this miss type, which doubles as the suffix of its `spellmisstypes.*` translation key.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Absorb    => 'absorb',
            self::Block     => 'block',
            self::Deflect   => 'deflect',
            self::Dodge     => 'dodge',
            self::Evade     => 'evade',
            self::Immune    => 'immune',
            self::Miss      => 'miss',
            self::Parry     => 'parry',
            self::Reflect   => 'reflect',
            self::Resist    => 'resist',
            self::Interrupt => 'interrupt',
        };
    }
}
