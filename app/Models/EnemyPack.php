<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $teeming
 * @property string $faction
 * @property string $label
 * @property string $vertices_json
 *
 * @property \App\Models\Floor $floor
 * @property \Illuminate\Support\Collection $beguilingenemies
 * @property \Illuminate\Support\Collection $enemies
 *
 * @mixin \Eloquent
 */
class EnemyPack extends Model
{
    protected $appends = ['beguilingenemyids'];
    public $timestamps = false;
    protected $hidden = ['beguilingenemies'];

    /**
     * @return array
     */
    public function getBeguilingEnemyIDsAttribute()
    {
        return $this->beguilingenemies->pluck('id')->toArray();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function floor()
    {
        return $this->belongsTo('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function beguilingenemies()
    {
        // Must order by, the enemies are shot in A-sync so we're not entirely sure of the order in the database.
        return $this->hasMany('App\Models\Enemy')->whereNotNull('beguiling_preset')->orderBy('beguiling_preset', 'ASC');
    }

    /**
     * Clears all beguiling NPCs from this enemy pack.
     */
    public function clearBeguilingEnemies()
    {
        $this->beguilingenemies()->delete();
    }
}
