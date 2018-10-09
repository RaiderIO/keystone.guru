<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @property $id int The ID of this DungeonRoute.
 * @property $author_id int
 * @property $dungeon_id int
 * @property $faction_id int
 * @property $public_key string
 * @property $title string
 * @property $difficulty string
 * @property $teeming boolean
 * @property $published boolean
 * @property $unlisted boolean
 * @property $demo boolean
 *
 * @property $setup array
 * @property $avg_rating double
 * @property $rating_count int
 * @property $enemy_forces int
 *
 * @property $dungeon Dungeon
 * @property $route Route
 * @property $faction Faction
 * @property $author User
 *
 * @property $specializations \Illuminate\Support\Collection
 * @property $classes \Illuminate\Support\Collection
 * @property $races \Illuminate\Support\Collection
 *
 * @property $playerspecializations \Illuminate\Support\Collection
 * @property $playerclasses \Illuminate\Support\Collection
 * @property $playerraces \Illuminate\Support\Collection
 *
 * @property $affixgroups \Illuminate\Support\Collection
 * @property $affixes \Illuminate\Support\Collection
 * @property $ratings \Illuminate\Support\Collection
 */
class DungeonRoute extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['setup', 'avg_rating', 'rating_count', 'enemy_forces'];

    protected $hidden = ['id', 'author_id', 'dungeon_id', 'faction_id', 'unlisted', 'demo', 'created_at', 'updated_at'];

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function routes()
    {
        return $this->hasMany('App\Models\Route');
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
    public function specializations()
    {
        return $this->belongsToMany('App\Models\CharacterClassSpecialization', 'dungeon_route_player_specializations');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function playerspecializations()
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerSpecialization');
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function killzones()
    {
        return $this->hasMany('App\Models\KillZone');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ratings()
    {
        return $this->hasMany('App\Models\DungeonRouteRating');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favorites()
    {
        return $this->hasMany('App\Models\DungeonRouteFavorite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enemyraidmarkers()
    {
        return $this->hasMany('App\Models\DungeonRouteEnemyRaidMarker');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mapcomments()
    {
        return $this->hasMany('App\Models\MapComment');
    }

    /**
     * @return double
     */
    public function getAvgRatingAttribute()
    {
        $avg = 1;
        if (!$this->ratings->isEmpty()) {
            /** @var Collection $ratings */
            $ratings = $this->ratings;
            $ratingsArr = $ratings->pluck(['rating'])->toArray();

            $avg = array_sum($ratingsArr) / count($ratingsArr);
        }

        return round($avg, 2);
    }

    /**
     * @return mixed Get the uniquely identifying context for this route.
     */
    public function getReportContext()
    {
        return $this->public_key;
    }

    /**
     * @return integer
     */
    public function getRatingCountAttribute()
    {
        return $this->ratings->count();
    }

    /**
     * Gets the current amount of enemy forces that have been targeted for killing in this dungeon route.
     */
    public function getEnemyForcesAttribute()
    {
        // Build an ID => amount array of NPCs we've killed in this route
        $killedNPCs = [];
        foreach ($this->killzones as $killzone) {
            /** @var KillZone $killzone */
            foreach ($killzone->enemies as $enemy) {
                /** @var Enemy $enemy */
                if (isset($killedNPCs[$enemy->npc_id])) {
                    $killedNPCs[$enemy->npc_id]++;
                } else {
                    $killedNPCs[$enemy->npc_id] = 1;
                }
            }
        }

        // Find all Npcs that we've killed
        $npcs = Npc::findMany(array_keys($killedNPCs));
        $result = 0;
        // Build the result
        foreach ($npcs as $npc) {
            // Only if they're set (> -1) and if it makes sense (> 0)
            if ($npc->enemy_forces > 0) {
                /** @var $npc Npc */
                $result += $killedNPCs[$npc->id] * $npc->enemy_forces;
            }
        }

        return $result;
    }

    /**
     * @return array The setup as used in the front-end.
     */
    public function getSetupAttribute()
    {
        return [
            'faction' => $this->faction,
            'specializations' => $this->specializations,
            'classes' => $this->classes,
            'races' => $this->races
        ];
    }

    /**
     * @return bool True if the route contains an affix group which contains the Teeming affix, false if this is not the case.
     */
    public function hasTeemingAffix()
    {
        $result = false;
        if ($this->affixes->count() !== 0) {
            foreach ($this->affixes as $affixGroup) {
                /** @var $affixGroup AffixGroup */
                if ($result = $affixGroup->isTeeming()) {
                    break;
                }
            }
        }

        return $result;
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
            $this->public_key = DungeonRoute::generateRandomPublicKey();
        }

        $this->dungeon_id = $request->get('dungeon_id', $this->dungeon_id);
        $this->faction_id = $request->get('faction_id', $this->faction_id);
        $this->title = $request->get('dungeon_route_title', $this->title);
        //$this->difficulty = $request->get('difficulty', $this->difficulty);
        $this->difficulty = 1;
        $this->teeming = $request->get('teeming', 0);

        if (Auth::user()->hasPaidTier('unlisted-routes')) {
            $this->unlisted = intval($request->get('unlisted', 0)) > 0;
        }
        $this->demo = intval($request->get('demo', 0)) > 0;

        // Update or insert it
        if ($this->save()) {

            $newSpecs = $request->get('specialization', array());
            if (!empty($newSpecs)) {
                // Remove old specializations
                $this->playerspecializations()->delete();
                foreach ($newSpecs as $key => $value) {
                    $drpSpec = new DungeonRoutePlayerSpecialization();
                    $drpSpec->character_class_specialization_id = $value;
                    $drpSpec->dungeon_route_id = $this->id;
                    $drpSpec->save();
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

            $newAffixes = $request->get('affixes', array());
            if (!empty($newAffixes)) {
                // Remove old affixgroups
                $this->affixgroups()->delete();
                foreach ($newAffixes as $key => $value) {
                    /** @var AffixGroup $affixGroup */
                    $affixGroup = AffixGroup::findOrNew($value);

                    // Do not add affixes that do not belong to our Teeming selection
                    if ($affixGroup->id > 0 && $this->teeming != $affixGroup->isTeeming()) {
                        continue;
                    }

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

    /**
     * @return int|bool Gets the rating the current user (whoever is logged in atm) has given this dungeon route.
     */
    public function getRatingByCurrentUser()
    {
        $result = false;
        $user = Auth::user();
        if ($user !== null) {
            // @TODO Probably going to want an index on this one
            $rating = DB::table('dungeon_route_ratings')
                ->where('dungeon_route_id', '=', $this->id)
                ->where('user_id', '=', $user->id)
                ->get(['rating'])->first();

            if ($rating !== null) {
                $result = $rating->rating;
            }
        }

        return $result;
    }

    /**
     * @return int|mixed
     */
    public function isFavoritedByCurrentUser()
    {
        $result = false;
        $user = Auth::user();
        if ($user !== null) {
            // @TODO Probably going to want an index on this one
            $favorite = DB::table('dungeon_route_favorites')
                ->where('dungeon_route_id', '=', $this->id)
                ->where('user_id', '=', $user->id)
                ->first();

            $result = $favorite !== null;
        }

        return $result;
    }

    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function ($item) {
            DungeonRoutePlayerRace::where('dungeon_route_id', '=', $item->id)->delete();
            DungeonRoutePlayerClass::where('dungeon_route_id', '=', $item->id)->delete();
            DungeonRouteAffixGroup::where('dungeon_route_id', '=', $item->id)->delete();
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', '=', $item->id)->delete();
            MapComment::where('dungeon_route_id', '=', $item->id)->delete();

            // Delete routes
            foreach ($item->routes as $route) {
                /** @var $route \App\Models\Route */
                $route->deleteVertices();
                $route->delete();
            }

            // Delete kill zones
            foreach ($item->killzones as $killzone) {
                /** @var $killzone \App\Models\KillZone */
                $killzone->deleteEnemies();
                $killzone->delete();
            }
        });
    }
}
