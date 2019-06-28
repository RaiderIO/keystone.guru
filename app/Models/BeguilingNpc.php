<?php

namespace App\Models;

/**
 * @property $id int
 * @property $enemy_pack_id int
 * @property $npc_id int
 * @property $set int The set of the Beguiling enemy, there's usually 2-3-4 sets of different combinations possible, this set says under which set it falls.
 *
 * @property EnemyPack $enemypack
 * @property Npc $npc
 *
 * @mixin \Eloquent
 */
class BeguilingNpc extends IconFileModel
{
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enemypack()
    {
        return $this->belongsTo('App\Models\EnemyPack');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function npc()
    {
        return $this->belongsTo('App\Models\NPC');
    }
}
