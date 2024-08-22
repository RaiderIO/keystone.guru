<?php

namespace App\Models\CombatLog;

use App\Models\Spell\Spell;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $spell_id
 * @property string|null $before
 * @property string      $after
 * @property string      $combat_log_path
 * @property string      $raw_event
 *
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 *
 * @property Spell       $spell
 *
 * @mixin Eloquent
 */
class CombatLogSpellUpdate extends Model
{
    protected $connection = 'combatlog';

    public $timestamps = true;

    protected $fillable = [
        'spell_id',
        'before',
        'after',
        'combat_log_path',
        'raw_event',
        'created_at',
        'updated_at',
    ];

    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
