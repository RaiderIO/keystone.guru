<?php

namespace App\Service\CombatLog\DataExtractors\Characteristics;

use App\Models\Characteristic;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellEffect;
use App\Models\Spell\SpellMechanic;

/**
 * Decides whether a spell landing on an NPC proves that NPC can be crowd controlled the way the spell's
 * characteristic says.
 *
 * A combat log only ever says that an aura was applied; it never says which of the spell's effects made
 * it stick. When a target is immune to one effect's mechanic the server drops that effect alone and
 * applies the rest, so a spell that carries a rider effect - Infected Wounds' damage amplifier, Garrote's
 * bleed - still produces a SPELL_AURA_APPLIED on a boss immune to the snare or the silence that spell is
 * curated for. Taking any application as proof is what had nearly every boss listed as slowable and
 * silenceable.
 *
 * A spell is therefore only conclusive when the crowd control is the *only* thing that could have carried
 * the aura:
 *
 * - its spell-level mechanic is one of the characteristic's, because immunity to a spell-level mechanic
 *   rejects the whole cast and no aura is logged at all; or
 * - every aura it applies is one of the characteristic's crowd control auras, so no rider effect exists
 *   that could have applied it to an immune target.
 *
 * Knock has neither: a knockback is not an aura and cannot be immunity-checked through one. Any aura a
 * knock spell applies is taken as proof on everything but a boss instead - bosses are immune to knocks,
 * trash rarely is, and a compendium where nothing is knockable would say nothing at all.
 */
final class CharacteristicEvidenceRule
{
    /** `SpellEffect.Effect` values that apply an aura to a hostile target. */
    private const array AURA_APPLYING_EFFECT_TYPES = [
        6,   // APPLY_AURA
        27,  // PERSISTENT_AREA_AURA
        129, // APPLY_AREA_AURA_ENEMY
    ];

    private const int AURA_MOD_POSSESS        = 2;
    private const int AURA_MOD_CONFUSE        = 5;
    private const int AURA_MOD_CHARM          = 6;
    private const int AURA_MOD_FEAR           = 7;
    private const int AURA_MOD_TAUNT          = 11;
    private const int AURA_MOD_STUN           = 12;
    private const int AURA_MOD_ROOT           = 26;
    private const int AURA_MOD_SILENCE        = 27;
    private const int AURA_MOD_DECREASE_SPEED = 33;
    private const int AURA_TRANSFORM          = 56;
    private const int AURA_MOD_DETECT_RANGE   = 91;
    private const int AURA_MOD_STUN_2         = 298;
    private const int AURA_MOD_ROOT_2         = 455;

    /**
     * Characteristics no aura can prove, for which any aura their spell applies counts as proof unless the
     * target is a boss.
     *
     * @var array<int, string>
     */
    private const array PROVEN_BY_ANY_AURA_EXCEPT_ON_BOSSES = [
        Characteristic::CHARACTERISTIC_KNOCK,
    ];

    /**
     * Per characteristic, the spell-level mechanics and the `SpellEffect.EffectAura` types that are that
     * crowd control itself rather than something riding along with it.
     *
     * An aura type says what the aura does, not which characteristic curated it - several characteristics
     * share one (everything that briefly takes a target out of the fight uses MOD_STUN), which is fine:
     * the spell being judged already carries the characteristic, and the question here is only whether
     * its aura could have landed without the crowd control landing.
     *
     * @var array<string, array{mechanics: array<int, SpellMechanic>, auras: array<int, int>}>
     */
    private const array EVIDENCE = [
        Characteristic::CHARACTERISTIC_TAUNT => [
            'mechanics' => [],
            'auras'     => [self::AURA_MOD_TAUNT],
        ],
        Characteristic::CHARACTERISTIC_INCAPACITATE => [
            'mechanics' => [SpellMechanic::Incapacitated],
            'auras'     => [self::AURA_MOD_STUN],
        ],
        Characteristic::CHARACTERISTIC_SUBJUGATE_DEMON => [
            'mechanics' => [SpellMechanic::Charmed],
            'auras'     => [self::AURA_MOD_CHARM],
        ],
        Characteristic::CHARACTERISTIC_CONTROL_UNDEAD => [
            'mechanics' => [SpellMechanic::Charmed],
            'auras'     => [self::AURA_MOD_CHARM],
        ],
        Characteristic::CHARACTERISTIC_SILENCE => [
            'mechanics' => [SpellMechanic::Silenced],
            'auras'     => [self::AURA_MOD_SILENCE],
        ],
        // A knockback is effect 98 and a grip effect 77 - neither is an aura, so an aura application says
        // nothing about either; knock is judged by PROVEN_BY_ANY_AURA_EXCEPT_ON_BOSSES instead
        Characteristic::CHARACTERISTIC_KNOCK => [
            'mechanics' => [],
            'auras'     => [],
        ],
        Characteristic::CHARACTERISTIC_GRIP => [
            'mechanics' => [SpellMechanic::Gripped],
            'auras'     => [],
        ],
        Characteristic::CHARACTERISTIC_SHACKLE_UNDEAD => [
            'mechanics' => [SpellMechanic::Shackled],
            'auras'     => [self::AURA_MOD_STUN],
        ],
        Characteristic::CHARACTERISTIC_MIND_CONTROL => [
            'mechanics' => [SpellMechanic::Charmed],
            'auras'     => [self::AURA_MOD_POSSESS],
        ],
        Characteristic::CHARACTERISTIC_POLYMORPH => [
            'mechanics' => [SpellMechanic::Polymorphed],
            'auras'     => [self::AURA_MOD_CONFUSE, self::AURA_TRANSFORM],
        ],
        Characteristic::CHARACTERISTIC_ROOT => [
            'mechanics' => [SpellMechanic::Rooted],
            'auras'     => [self::AURA_MOD_ROOT, self::AURA_MOD_ROOT_2],
        ],
        Characteristic::CHARACTERISTIC_FEAR => [
            'mechanics' => [SpellMechanic::Fleeing, SpellMechanic::Horrified],
            'auras'     => [self::AURA_MOD_FEAR],
        ],
        Characteristic::CHARACTERISTIC_BANISH => [
            'mechanics' => [SpellMechanic::Banished],
            'auras'     => [],
        ],
        Characteristic::CHARACTERISTIC_DISORIENT => [
            'mechanics' => [SpellMechanic::Disoriented],
            'auras'     => [self::AURA_MOD_CONFUSE],
        ],
        Characteristic::CHARACTERISTIC_REPENTANCE => [
            'mechanics' => [SpellMechanic::Incapacitated],
            'auras'     => [self::AURA_MOD_STUN],
        ],
        Characteristic::CHARACTERISTIC_IMPRISON => [
            'mechanics' => [SpellMechanic::Sapped, SpellMechanic::Incapacitated],
            'auras'     => [self::AURA_MOD_STUN],
        ],
        Characteristic::CHARACTERISTIC_SAP => [
            'mechanics' => [SpellMechanic::Sapped],
            'auras'     => [self::AURA_MOD_STUN],
        ],
        Characteristic::CHARACTERISTIC_STUN => [
            'mechanics' => [SpellMechanic::Stunned],
            'auras'     => [self::AURA_MOD_STUN, self::AURA_MOD_STUN_2],
        ],
        Characteristic::CHARACTERISTIC_SLOW => [
            'mechanics' => [SpellMechanic::Snared, SpellMechanic::Dazed, SpellMechanic::Slowed],
            'auras'     => [self::AURA_MOD_DECREASE_SPEED],
        ],
        Characteristic::CHARACTERISTIC_SLEEP_WALK => [
            'mechanics' => [SpellMechanic::Asleep],
            'auras'     => [self::AURA_MOD_FEAR],
        ],
        Characteristic::CHARACTERISTIC_SCARE_BEAST => [
            'mechanics' => [SpellMechanic::Fleeing],
            'auras'     => [self::AURA_MOD_FEAR],
        ],
        Characteristic::CHARACTERISTIC_HIBERNATE => [
            'mechanics' => [SpellMechanic::Asleep],
            'auras'     => [self::AURA_MOD_STUN],
        ],
        Characteristic::CHARACTERISTIC_TURN_EVIL => [
            'mechanics' => [SpellMechanic::Turned],
            'auras'     => [self::AURA_MOD_FEAR],
        ],
        Characteristic::CHARACTERISTIC_MIND_SOOTHE => [
            'mechanics' => [],
            'auras'     => [self::AURA_MOD_DETECT_RANGE],
        ],
    ];

    /**
     * Whether this spell landing on an NPC proves the NPC is affected by its characteristic's crowd
     * control. Needs the spell's effects loaded; a spell we hold no effect data for is never conclusive,
     * because there is then nothing that rules a rider effect out.
     */
    public static function isConclusive(Spell $spell): bool
    {
        $characteristicKey = array_flip(Characteristic::ALL)[$spell->characteristic_id] ?? '';
        $evidence          = self::EVIDENCE[$characteristicKey] ?? null;

        if ($evidence === null) {
            return false;
        }

        if (in_array($characteristicKey, self::PROVEN_BY_ANY_AURA_EXCEPT_ON_BOSSES, true)) {
            return $spell->spellEffects->contains(
                static fn(SpellEffect $spellEffect): bool => in_array($spellEffect->effect_type, self::AURA_APPLYING_EFFECT_TYPES, true),
            );
        }

        foreach ($evidence['mechanics'] as $mechanic) {
            if ($spell->mechanic === $mechanic->translationKey()) {
                return true;
            }
        }

        if ($evidence['auras'] === []) {
            return false;
        }

        $auraEffects = $spell->spellEffects->filter(
            static fn(SpellEffect $spellEffect): bool => in_array($spellEffect->effect_type, self::AURA_APPLYING_EFFECT_TYPES, true),
        );

        return $auraEffects->isNotEmpty() && $auraEffects->every(
            static fn(SpellEffect $spellEffect): bool => in_array($spellEffect->aura_type, $evidence['auras'], true),
        );
    }

    /**
     * Whether a spell that {@see self::isConclusive()} also proves its characteristic on this particular
     * NPC. Only false for a boss hit by a characteristic in PROVEN_BY_ANY_AURA_EXCEPT_ON_BOSSES.
     */
    public static function isConclusiveOn(Spell $spell, Npc $npc): bool
    {
        if (!$npc->isBoss()) {
            return true;
        }

        $characteristicKey = array_flip(Characteristic::ALL)[$spell->characteristic_id] ?? '';

        return !in_array($characteristicKey, self::PROVEN_BY_ANY_AURA_EXCEPT_ON_BOSSES, true);
    }
}
