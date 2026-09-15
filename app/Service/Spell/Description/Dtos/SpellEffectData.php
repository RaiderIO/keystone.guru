<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * A single row of the game client's SpellEffect DB2 table, reduced to what spell descriptions refer to.
 */
class SpellEffectData
{
    public function __construct(
        public readonly int    $effectType,
        public readonly int    $auraType,
        public readonly float  $basePoints,
        public readonly float  $variance,
        public readonly int    $periodMs,
        public readonly int    $chainTargets,
        public readonly ?float $radius,
        public readonly ?float $maxRadius,
    ) {
    }

    /**
     * Whether this effect's points can be shown at all.
     *
     * Creature abilities carry no base points and derive their value from the content they belong to.
     * Those effects are exactly the ones that sit at zero, so a zero is read as "unknown" rather than
     * shown, which would claim an ability deals no damage at all.
     */
    public function hasKnownPoints(): bool
    {
        return $this->basePoints !== 0.0;
    }

    /**
     * What this effect's points mean, which decides whether they scale with the content the caster
     * belongs to. Anything that is not an amount of damage or healing - a percentage, a stat modifier,
     * a number of targets - is the same at every key level.
     */
    public function getPointsKind(): SpellDescriptionValueKind
    {
        return SpellEffectType::tryFrom($this->effectType)?->getPointsKind()
            ?? SpellEffectAuraType::tryFrom($this->auraType)?->getPointsKind()
            ?? SpellDescriptionValueKind::Value;
    }

    /**
     * The row this effect is stored as, so that a field added to the effect cannot be forgotten by the
     * import that persists it.
     *
     * The spell and the index it sits at are the effect's place in the table rather than its own state,
     * which is why they are passed in.
     *
     * @return array{spell_id: int, effect_index: int, effect_type: int, aura_type: int, base_points: float, variance: float, period_ms: int, chain_targets: int, radius: float|null, max_radius: float|null}
     */
    public function toArray(int $spellId, int $effectIndex): array
    {
        return [
            'spell_id'      => $spellId,
            'effect_index'  => $effectIndex,
            'effect_type'   => $this->effectType,
            'aura_type'     => $this->auraType,
            'base_points'   => $this->basePoints,
            'variance'      => $this->variance,
            'period_ms'     => $this->periodMs,
            'chain_targets' => $this->chainTargets,
            'radius'        => $this->radius,
            'max_radius'    => $this->maxRadius,
        ];
    }

    /** The lowest value this effect rolls, i.e. `$m` in a description. */
    public function getMinPoints(): float
    {
        return $this->basePoints - ($this->basePoints * $this->variance / 2);
    }

    /** The highest value this effect rolls, i.e. `$M` in a description. */
    public function getMaxPoints(): float
    {
        return $this->basePoints + ($this->basePoints * $this->variance / 2);
    }
}
