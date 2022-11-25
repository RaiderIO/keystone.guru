<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $color
 *
 * @property Collection|CharacterClassSpecialization[] $specializations
 * @property Collection|DungeonRoutePlayerClass[] $dungeonrouteplayerclasses
 * @property Collection|DungeonRoutePlayerRace[] $dungeonrouteplayerraces
 *
 * @mixin Eloquent
 */
class CharacterClass extends CacheModel
{
    use HasIconFile;

    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];
    public $fillable = ['key', 'name', 'color'];

    /**
     * @return HasMany
     */
    public function specializations(): HasMany
    {
        return $this->hasMany(CharacterClassSpecialization::class);
    }

    /**
     * @return BelongsToMany
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(CharacterRace::class, 'character_race_class_couplings');
    }

    /**
     * @return BelongsToMany
     */
    public function dungeonrouteplayerclasses(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerClass::class, 'dungeon_route_player_classes');
    }

    /**
     * @return BelongsToMany
     */
    public function dungeonrouteplayerraces(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerRace::class, 'dungeon_route_player_races');
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
