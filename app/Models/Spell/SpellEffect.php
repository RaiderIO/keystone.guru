<?php

namespace App\Models\Spell;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One effect of a spell, as the game client stores it.
 *
 * Creature damage and healing are coefficients rather than amounts: the amount a player sees is this
 * multiplied by a constant belonging to the content the caster is part of. Keeping the coefficient is
 * what lets an amount be recalculated for a given key level rather than baked in once.
 *
 * @property int        $id
 * @property int        $spell_id
 * @property int        $effect_index  0-based, i.e. `$s1` in a description asks for index 0
 * @property int        $effect_type
 * @property int        $aura_type
 * @property float      $base_points
 * @property float      $variance
 * @property int        $period_ms
 * @property int        $chain_targets
 * @property float|null $radius
 * @property float|null $max_radius
 *
 * @property Spell $spell
 */
class SpellEffect extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'spell_id',
        'effect_index',
        'effect_type',
        'aura_type',
        'base_points',
        'variance',
        'period_ms',
        'chain_targets',
        'radius',
        'max_radius',
    ];

    protected function casts(): array
    {
        return [
            'spell_id'      => 'integer',
            'effect_index'  => 'integer',
            'effect_type'   => 'integer',
            'aura_type'     => 'integer',
            'base_points'   => 'float',
            'variance'      => 'float',
            'period_ms'     => 'integer',
            'chain_targets' => 'integer',
            'radius'        => 'float',
            'max_radius'    => 'float',
        ];
    }

    /** The lowest value this effect rolls, i.e. `$m` in a description. */
    public function getMinPoints(): float
    {
        return $this->base_points - ($this->base_points * $this->variance / 2);
    }

    /** The highest value this effect rolls, i.e. `$M` in a description. */
    public function getMaxPoints(): float
    {
        return $this->base_points + ($this->base_points * $this->variance / 2);
    }

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
