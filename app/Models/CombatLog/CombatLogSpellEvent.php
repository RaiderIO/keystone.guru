<?php

namespace App\Models\CombatLog;

use App\Models\Spell\Spell;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int                     $id
 * @property int                     $spell_id
 * @property CombatLogSpellEventType $event_type
 * @property SpellProperty|null      $property
 * @property string|null             $combat_log_path
 *
 * @property Carbon $created_at
 *
 * @property Spell $spell
 *
 * @mixin Eloquent
 */
class CombatLogSpellEvent extends Model
{
    protected $connection = 'combatlog';

    public const UPDATED_AT = null;

    protected $fillable = [
        'spell_id',
        'event_type',
        'property',
        'combat_log_path',
    ];

    public function casts(): array
    {
        return [
            'event_type' => CombatLogSpellEventType::class,
            'property'   => SpellProperty::class,
        ];
    }

    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
