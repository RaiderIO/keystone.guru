<?php

namespace App\Models\CombatLog;

use App\Models\Spell\Spell;

enum SpellProperty: string
{
    case Aura        = 'aura';
    case Debuff      = 'debuff';
    case MissAbsorb  = 'miss_absorb';
    case MissBlock   = 'miss_block';
    case MissDeflect = 'miss_deflect';
    case MissDodge   = 'miss_dodge';
    case MissEvade   = 'miss_evade';
    case MissImmune  = 'miss_immune';
    case MissMiss    = 'miss_miss';
    case MissParry   = 'miss_parry';
    case MissReflect = 'miss_reflect';
    case MissResist  = 'miss_resist';

    public static function fromMissTypeBit(int $bit): self
    {
        return self::from(sprintf('miss_%s', Spell::ALL_MISS_TYPES[$bit]));
    }
}
