<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $randomcolumn int
 * @property $affix \Illuminate\Database\Eloquent\Collection
 */
class InfestedEnemyVote extends Model
{

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
