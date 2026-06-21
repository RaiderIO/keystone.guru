<?php

namespace App\Models\Spell;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Traits\SeederModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $spell_id
 * @property int $dungeon_id
 *
 * @property Spell   $spell
 * @property Dungeon $dungeon
 */
class SpellDungeon extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'spell_id',
        'dungeon_id',
    ];

    protected $visible = [
        'dungeon_id',
    ];

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    /** @return BelongsTo<Dungeon, $this> */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
