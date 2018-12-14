<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

/**
 * @property int $id The ID of this Dungeon.
 * @property int $expansion_id The linked expansion to this dungeon.
 * @property string $name The name of the dungeon.
 * @property int $enemy_forces_required The amount of total enemy forces required to complete the dungeon.
 * @property int $enemy_forces_required_teeming The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property boolean $active True if this dungeon is active, false if it is not.
 * @property Expansion $expansion
 * @property \Illuminate\Support\Collection $floors
 * @property \Illuminate\Support\Collection $dungeonroutes
 *
 * @method static \Illuminate\Database\Eloquent\Builder active()
 */
class Dungeon extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['key', 'floor_count'];
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
                if ($npc !== null && $npc->classification_id !== 3) {
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function npcs()
    {
        return $this->hasMany('App\Models\Npc');
    }

    /**
     * Scope a query to only the Siege of Boralus dungeon.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSiegeOfBoralus($query)
    {
        return $query->where('name', 'Siege of Boralus');
    }

    /**
     * Scope a query to only include active dungeons.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('active', 0);
    }

    /**
     * Get all yes and no votes for all dungeons.
     * @param $affixGroupId
     * @return array
     */
    public static function getInfestedEnemyStatus($affixGroupId)
    {
        $result = DB::select($query = '
                SELECT `dungeons`.`id`,
                       CAST(SUM(if(`vote` = 1, 1, 0)) as SIGNED)               as infested_yes_votes,
                       CAST(SUM(if(`vote` = 0, 1, 0)) as SIGNED)               as infested_no_votes,
                       CAST(SUM(if(
                             IFNULL(if(`vote` = 1, 1, 0) * `vote_weight`, 0) -
                             IFNULL(if(`vote` = 0, 1, 0) * `vote_weight`, 0) >= :infestedThreshold, 1, 0)) as SIGNED) as infested_enemies
                FROM `enemy_infested_votes`
                       LEFT JOIN `enemies` ON `enemies`.`id` = `enemy_infested_votes`.`enemy_id`
                       LEFT JOIN `floors` ON `floors`.`id` = `enemies`.`floor_id`
                       INNER JOIN `dungeons` ON `dungeons`.`id` = `floors`.`dungeon_id`
                WHERE `enemy_infested_votes`.affix_group_id = :affixGroupId
                AND `enemy_infested_votes`.updated_at > :minTime
                AND `dungeons`.`active` = 1
                GROUP BY `dungeons`.`id`;
                ', $params = [
            'infestedThreshold' => config('keystoneguru.infested_user_vote_threshold'),
            'affixGroupId' => $affixGroupId,
            // Of the last month only
            'minTime' => Carbon::now()->subMonth()->format('Y-m-d H:i:s')
        ]);

        // Set the ID column as a key for easy isset() usage later
        return array_combine(array_column($result, 'id'), $result);
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
