<?php

namespace App\Models\Spell;

/**
 * IDs of specific player spells the code treats as special cases.
 */
final class KnownSpell
{
    public const int BLOODLUST             = 2825;
    public const int HEROISM               = 32182;
    public const int TIME_WARP             = 80353;
    public const int FURY_OF_THE_ASPECTS   = 390386;
    public const int ANCIENT_HYSTERIA      = 90355;
    public const int PRIMAL_RAGE           = 264667;
    public const int FERAL_HIDE_DRUMS      = 381301;
    public const int THUNDEROUS_DRUMS      = 444257;
    public const int HARRIERS_CRY          = 466904;
    public const int SHROUD_OF_CONCEALMENT = 114018;
    public const int CONTROL_UNDEAD        = 111673;
    public const int SUBJUGATE_DEMON       = 1098;

    /**
     * Player defensives that grant an immunity window. Full immunities first, then the partial ones - what each of
     * them is actually supposed to stop is declared by its ImmunityDefinitionInterface, not by membership of this list.
     */
    public const int DIVINE_SHIELD            = 642;
    public const int ICE_BLOCK                = 45438;
    public const int ASPECT_OF_THE_TURTLE     = 186265;
    public const int BLESSING_OF_PROTECTION   = 1022;
    public const int BLESSING_OF_SPELLWARDING = 204018;
    public const int ANTI_MAGIC_SHELL         = 48707;

    public const array IMMUNITY_SPELLS = [
        self::DIVINE_SHIELD,
        self::ICE_BLOCK,
        self::ASPECT_OF_THE_TURTLE,
        self::BLESSING_OF_PROTECTION,
        self::BLESSING_OF_SPELLWARDING,
        self::ANTI_MAGIC_SHELL,
    ];

    public const array CHARM_SPELLS = [
        self::CONTROL_UNDEAD,
        self::SUBJUGATE_DEMON,
    ];

    public const array BLOODLUSTY_SPELLS = [
        self::BLOODLUST,
        self::HEROISM,
        self::TIME_WARP,
        self::FURY_OF_THE_ASPECTS,
        self::ANCIENT_HYSTERIA,
        self::PRIMAL_RAGE,
        self::FERAL_HIDE_DRUMS,
        self::THUNDEROUS_DRUMS,
        self::HARRIERS_CRY,
    ];
}
