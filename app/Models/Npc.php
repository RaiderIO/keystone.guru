<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsTo;
use Illuminate\Database\Eloquent\Relations\hasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $classification_id
 * @property int $npc_type_id
 * @property int $npc_class_id
 * @property string $name
 * @property int $base_health
 * @property int $enemy_forces
 * @property int $enemy_forces_teeming
 * @property string $aggressiveness
 * @property bool $dangerous
 * @property bool $truesight
 * @property bool $bursting
 * @property bool $bolstering
 * @property bool $sanguine
 *
 * @property Dungeon $dungeon
 * @property NpcClassification $classification
 * @property NpcType $type
 * @property NpcClass $class
 *
 * @property Enemy[]|Collection $enemies
 * @property NpcBolsteringWhitelist[]|Collection $npcbolsteringwhitelists
 *
 * @mixin Eloquent
 */
class Npc extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $with = ['type', 'class', 'npcbolsteringwhitelists'];
    protected $fillable = ['id', 'npc_type_id', 'npc_class_id', 'dungeon_id', 'name', 'base_health', 'enemy_forces', 'enemy_forces_teeming', 'aggressiveness'];

    /**
     * @return bool
     */
    public function isAwakened()
    {
        return in_array($this->id, [161244, 161243, 161124, 161241]);
    }

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return hasMany
     */
    function enemies()
    {
        return $this->hasMany('App\Models\Enemy');
    }

    /**
     * @return hasMany
     */
    function npcbolsteringwhitelists()
    {
        return $this->hasMany('App\Models\NpcBolsteringWhitelist');
    }

    /**
     * @return belongsTo
     */
    function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return belongsTo
     */
    function classification()
    {
        return $this->belongsTo('App\Models\NpcClassification');
    }

    /**
     * @return belongsTo
     */
    function type()
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo('App\Models\NpcType', 'npc_type_id');
    }

    /**
     * @return belongsTo
     */
    function class()
    {
        // Not sure why the foreign key declaration is required here, but it is
        return $this->belongsTo('App\Models\NpcClass', 'npc_class_id');
    }
}
