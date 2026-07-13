<?php

namespace App\Models\CombatLog;

use App\Models\Spell\Spell;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int           $id
 * @property int           $spell_id
 * @property SpellProperty $property
 * @property Carbon        $observed_on
 * @property string        $combat_log_path
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Spell $spell
 *
 * @mixin Eloquent
 */
class CombatLogSpellPropertyObservation extends Model
{
    protected $connection = 'combatlog';

    protected $fillable = [
        'spell_id',
        'property',
        'observed_on',
        'combat_log_path',
    ];

    public function casts(): array
    {
        return [
            'property'    => SpellProperty::class,
            'observed_on' => 'date',
        ];
    }

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
