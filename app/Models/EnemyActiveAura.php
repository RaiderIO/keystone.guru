<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $enemy_id
 * @property int $spell_id
 *
 * @property Enemy $enemy
 * @property Spell $spell
 *
 * @mixin Eloquent
 */
class EnemyActiveAura extends CacheModel
{
    public $visible = ['id', 'enemy_id', 'spell_id'];
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function enemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    /**
     * @return BelongsTo
     */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }
}
