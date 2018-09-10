<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mockery\Exception;

/**
 * @property $id int The ID of this Dungeon.
 * @property $expansion_id int The linked expansion to this dungeon.
 * @property $name string The name of the dungeon.
 * @property $enemy_forces_required int The amount of total enemy forces required to complete the dungeon.
 * @property $enemy_forces_required_teeming int The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property $active boolean True if this dungeon is active, false if it is not.
 * @property $expansion \Expansion
 * @property $floors \Illuminate\Support\Collection
 * @property $dungeonroutes \Illuminate\Support\Collection
 * @function active
 */
class Dungeon extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['key', 'enemy_forces_mapped_status'];
    public $with = ['expansion'];

    public $hidden = ['expansion_id', 'created_at', 'updated_at'];
    public $timestamps = false;

    /**
     * @return string The key as used in the front-end to identify the dungeon.
     */
    public function getKeyAttribute()
    {
        // https://stackoverflow.com/questions/14114411/remove-all-special-characters-from-a-string
        $string = str_replace(' ', '', strtolower($this->name)); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    /**
     * Gets the amount of enemy forces that this dungeon has mapped (non-zero enemy_forces on NPCs)
     */
    public function getEnemyForcesMappedStatusAttribute()
    {
        $result = [];
        $npcs = [];

        try {
            // Loop through all floors
            foreach ($this->floors as $floor) {
                /** @var $floor Floor */
                foreach ($floor->enemies as $enemy) {
                    /** @var $enemy Enemy */
                    // Keep track which enemy has enemy_forces filled vrs not, we do it like this
                    // because there can be multiple enemies with the same npc, this prevents those from counting double
                    if ($enemy->npc !== null) {
                        $npcs[$enemy->npc_id] = $enemy->npc->enemy_forces >= 0;
                    }
                }
            }
        } catch (Exception $ex) {
            dd($ex);
        }

        // Calculate which ones are unmapped
        $unmappedCount = 0;
        foreach ($npcs as $id => $npc) {
            if (!$npc) {
                $unmappedCount++;
            }
        }

        $total = count($npcs);
        $result['npcs'] = $npcs;
        $result['unmapped'] = $unmappedCount;
        $result['total'] = $total;
        $result['percent'] = $total <= 0 ? 0 : 100 - (($unmappedCount / $total) * 100);

        return $result;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expansion()
    {
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function floors()
    {
        return $this->hasMany('App\Models\Floor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('active', 0);
    }
}
