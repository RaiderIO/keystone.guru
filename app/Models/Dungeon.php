<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Mockery\Exception;

/**
 * @property int $id The ID of this Dungeon.
 * @property int $expansion_id The linked expansion to this dungeon.
 * @property int $zone_id The ID of the location that WoW has given this dungeon.
 * @property string $name The name of the dungeon.
 * @property string $key Shorthand key of the dungeon
 * @property int $enemy_forces_required The amount of total enemy forces required to complete the dungeon.
 * @property int $enemy_forces_required_teeming The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property int $timer_max_seconds The maximum timer (in seconds) that you have to complete the dungeon.
 * @property boolean $active True if this dungeon is active, false if it is not.
 *
 * @property Expansion $expansion
 *
 * @property Collection|Floor[] $floors
 * @property Collection|DungeonRoute[] $dungeonroutes
 * @property Collection|Npc[] $npcs
 *
 * @property Collection|Enemy[] $enemies
 * @property Collection|EnemyPack[] $enemypacks
 * @property Collection|EnemyPatrol[] $enemypatrols
 * @property Collection|MapIcon[] $mapicons
 * @property Collection|DungeonFloorSwitchMarker[] $floorswitchmarkers
 *
 * @method static Builder active()
 * @method static Builder inactive()
 *
 * @mixin Eloquent
 */
class Dungeon extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['floor_count'];
    public $with = ['expansion', 'floors'];

    public $hidden = ['expansion_id', 'created_at', 'updated_at'];
    public $timestamps = false;

    /**
     * @return int The amount of floors this dungeon has.
     */
    public function getFloorCountAttribute()
    {
        return $this->floors->count();
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
            foreach ($this->npcs as $npc) {
                /** @var $npc Npc */
                // @TODO Hard coded boss?
                if ($npc !== null && $npc->classification_id < 3) {
                    $npcs[$npc->id] = $npc->enemy_forces >= 0;
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
     * @return BelongsTo
     */
    public function expansion()
    {
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return HasMany
     */
    public function floors()
    {
        return $this->hasMany('App\Models\Floor')->orderBy('index');
    }

    /**
     * @return HasMany
     */
    public function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
    }

    /**
     * @return HasMany
     */
    public function npcs()
    {
        return $this->hasMany('App\Models\Npc')->orWhere('dungeon_id', -1);
    }

    /**
     * @return HasManyThrough
     */
    public function enemies()
    {
        return $this->hasManyThrough('App\Models\Enemy', 'App\Models\Floor');
    }

    /**
     * @return HasManyThrough
     */
    public function enemypacks()
    {
        return $this->hasManyThrough('App\Models\EnemyPack', 'App\Models\Floor');
    }

    /**
     * @return HasManyThrough
     */
    public function enemypatrols()
    {
        return $this->hasManyThrough('App\Models\EnemyPatrol', 'App\Models\Floor');
    }

    /**
     * @return HasManyThrough
     */
    public function mapicons()
    {
        return $this->hasManyThrough('App\Models\MapIcon', 'App\Models\Floor')->where('dungeon_route_id', -1);
    }

    /**
     * @return HasManyThrough
     */
    public function floorswitchmarkers()
    {
        return $this->hasManyThrough('App\Models\DungeonFloorSwitchMarker', 'App\Models\Floor');
    }

    /**
     * Scope a query to only the Siege of Boralus dungeon.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSiegeOfBoralus($query)
    {
        return $query->where('name', 'Siege of Boralus');
    }

    /**
     * Scope a query to only include active dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('active', 0);
    }


    /**
     * Get the minimum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMinHealth()
    {
        return $this->npcs->where('classification_id', '<', 3)->where('dungeon_id', '<>', -1)->min('base_health') ?? 10000;
    }

    /**
     * Get the maximum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMaxHealth()
    {
        return $this->npcs->where('classification_id', '<', 3)->where('dungeon_id', '<>', -1)->max('base_health') ?? 100000;
    }

    /**
     * Checks if this dungeon is Siege of Boralus. It's a bit of a special dungeon because of horde/alliance differences,
     * hence this function so we can use it differentiate between the two.
     *
     * @return bool
     */
    public function isSiegeOfBoralus()
    {
        return $this->name === 'Siege of Boralus';
    }

    /**
     * Checks if this dungeon is Tol Dagor. It's a bit of a special dungeon because of a shitty MDT bug.
     *
     * @return bool
     */
    public function isTolDagor()
    {
        return $this->name === 'Tol Dagor';
    }

    public function getTimerUpgradePlusTwoSeconds(){
        return $this->timer_max_seconds * config('keystoneguru.timer.plustwofactor');
    }

    public function getTimerUpgradePlusThreeSeconds() {
        return $this->timer_max_seconds * config('keystoneguru.timer.plusthreefactor');
    }


    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel)
        {
            return false;
        });
    }
}
