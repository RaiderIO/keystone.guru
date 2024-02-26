<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int   $id
 * @property int   $enemy_id
 * @property int   $spell_id
 * @property Enemy $enemy
 * @property Spell $spell
 *
 * @mixin Eloquent
 */
class EnemyActiveAura extends CacheModel
{
    use SeederModel;

    public $visible = ['id', 'enemy_id', 'spell_id'];

    public $timestamps = false;

    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
