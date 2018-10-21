<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vote
 * @property User $user
 * @property Enemy $enemy
 */
class EnemyInfestedVote extends Model
{
    public $fillable = ['enemy_id', 'user_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function enemy()
    {
        return $this->belongsTo('App\Models\Enemy');
    }
}
