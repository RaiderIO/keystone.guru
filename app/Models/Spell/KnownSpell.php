<?php

namespace App\Models\Spell;

/**
 * IDs of specific player spells the code treats as special cases. The backing value is the spell's
 * `spells`.`id`, so a case can be compared against an id straight from the database or a combat log.
 */
enum KnownSpell: int
{
    case Bloodlust           = 2825;
    case Heroism             = 32182;
    case TimeWarp            = 80353;
    case FuryOfTheAspects    = 390386;
    case AncientHysteria     = 90355;
    case PrimalRage          = 264667;
    case FeralHideDrums      = 381301;
    case ThunderousDrums     = 444257;
    case HarriersCry         = 466904;
    case ShroudOfConcealment = 114018;
    case ControlUndead       = 111673;
    case SubjugateDemon      = 1098;

    /**
     * Player defensives that grant an immunity window. Full immunities first, then the partial ones - what each of
     * them is actually supposed to stop is declared by its ImmunityDefinitionInterface, not by membership of this list.
     */
    case DivineShield           = 642;
    case IceBlock               = 45438;
    case AspectOfTheTurtle      = 186265;
    case BlessingOfProtection   = 1022;
    case BlessingOfSpellwarding = 204018;
    case AntiMagicShell         = 48707;

    /** @var list<int> */
    public const array IMMUNITY_SPELLS = [
        self::DivineShield->value,
        self::IceBlock->value,
        self::AspectOfTheTurtle->value,
        self::BlessingOfProtection->value,
        self::BlessingOfSpellwarding->value,
        self::AntiMagicShell->value,
    ];

    /** @var list<int> */
    public const array CHARM_SPELLS = [
        self::ControlUndead->value,
        self::SubjugateDemon->value,
    ];

    /** @var list<int> */
    public const array BLOODLUSTY_SPELLS = [
        self::Bloodlust->value,
        self::Heroism->value,
        self::TimeWarp->value,
        self::FuryOfTheAspects->value,
        self::AncientHysteria->value,
        self::PrimalRage->value,
        self::FeralHideDrums->value,
        self::ThunderousDrums->value,
        self::HarriersCry->value,
    ];
}
