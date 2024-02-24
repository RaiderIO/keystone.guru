<?php

namespace App\Models\KillZone;

use App\Models\Spell;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int      $id
 * @property int      $kill_zone_id
 * @property int      $spell_id
 *
 * @property KillZone $killzone
 * @property Spell    $spell
 *
 * @mixin Eloquent
 */
class KillZoneSpell extends Model
{
    public $hidden = ['id', 'kill_zone_id'];

    public $timestamps = false;

    protected $fillable = [
        'kill_zone_id',
        'spell_id',
    ];

    /**
     * @return BelongsTo
     */
    public function killzone(): BelongsTo
    {
        return $this->belongsTo(KillZone::class);
    }

    /**
     * @return BelongsTo
     */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
