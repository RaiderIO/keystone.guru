<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @property $id int The ID of this DungeonRoute.
 * @property $author_id int
 * @property $dungeon_id int
 * @property $faction_id int
 * @property $public_key string
 * @property $title string
 * @property $unlisted boolean
 * @property $dungeon Dungeon
 * @property $route Route
 * @property $faction Faction
 * @property $author User
 * @property $races \Illuminate\Support\Collection
 * @property $classes \Illuminate\Support\Collection
 * @property $affixgroups \Illuminate\Support\Collection
 * @property $playerclasses \Illuminate\Support\Collection
 * @property $playerraces \Illuminate\Support\Collection
 * @property $affixes \Illuminate\Support\Collection
 */
class DungeonRoute extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['setup'];

    protected $hidden = ['id', 'author_id', 'dungeon_id', 'faction_id', 'unlisted', 'created_at', 'updated_at'];

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'public_key';
    }

    /**
     * @return string Generates a random public key that is displayed to the user in the URL.
     */
    public static function generateRandomPublicKey()
    {
        do {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $newKey = '';
            for ($i = 0; $i < 7; $i++) {
                $newKey .= $characters[rand(0, $charactersLength - 1)];
            }
        } while (DungeonRoute::all()->where('public_key', '=', $newKey)->count() > 0);

        return $newKey;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dungeon()
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function route()
    {
        return $this->hasOne('App\Models\Route');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function faction()
    {
        return $this->belongsTo('App\Models\Faction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function races()
    {
        return $this->belongsToMany('App\Models\CharacterRace', 'dungeon_route_player_races');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function playerraces()
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerRace');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function classes()
    {
        return $this->belongsToMany('App\Models\CharacterClass', 'dungeon_route_player_classes');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function playerclasses()
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerClass');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function affixgroups()
    {
        return $this->hasMany('App\Models\DungeonRouteAffixGroup');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function affixes()
    {
        return $this->belongsToMany('App\Models\AffixGroup', 'dungeon_route_affix_groups');
    }

    /**
     * @return array The setup as used in the front-end.
     */
    public function getSetupAttribute()
    {
        return [
            'faction' => $this->faction,
            'classes' => $this->classes,
            'races' => $this->races
        ];
    }

    /**
     * Saves this DungeonRoute with information from the passed Request.
     *
     * @param Request $request
     * @return bool
     */
    public function saveFromRequest(Request $request)
    {
        $result = false;

        // Overwrite the author_id if it's not been set yet
        if (!isset($this->id)) {
            $this->author_id = \Auth::user()->id;
        }

        $this->public_key = DungeonRoute::generateRandomPublicKey();
        $this->dungeon_id = $request->get('dungeon_id', $this->dungeon_id);
        $this->faction_id = $request->get('faction_id', $this->faction_id);
        $this->title = $request->get('dungeon_route_title', $this->title);
        $this->unlisted = intval($request->get('unlisted', 0)) > 0;

        // Update or insert it
        if ($this->save()) {
            $newRaces = $request->get('race', array());

            if (!empty($newRaces)) {
                // Remove old races
                $this->playerraces()->delete();

                // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
                foreach ($newRaces as $key => $value) {
                    $drpRace = new DungeonRoutePlayerRace();
                    $drpRace->character_race_id = $value;
                    $drpRace->dungeon_route_id = $this->id;
                    $drpRace->save();
                }
            }

            $newClasses = $request->get('class', array());
            if (!empty($newClasses)) {
                // Remove old classes
                $this->playerclasses()->delete();
                foreach ($newClasses as $key => $value) {
                    $drpClass = new DungeonRoutePlayerClass();
                    $drpClass->character_class_id = $value;
                    $drpClass->dungeon_route_id = $this->id;
                    $drpClass->save();
                }
            }

            $newAffixes = $request->get('affixes', array());
            if (!empty($newAffixes)) {
                // Remove old affixgroups
                $this->affixgroups()->delete();
                foreach ($newAffixes as $key => $value) {
                    $drAffixGroup = new DungeonRouteAffixGroup();
                    $drAffixGroup->affix_group_id = $value;
                    $drAffixGroup->dungeon_route_id = $this->id;
                    $drAffixGroup->save();
                }
            }
            $result = true;
        }

        return $result;
    }
}
