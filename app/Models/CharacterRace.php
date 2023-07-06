<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $faction_id
 * @property Faction $faction
 * @property Collection $classes
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterRace extends CacheModel
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];
    public $fillable = ['key', 'name'];

    /**
     * @return BelongsToMany
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClass::class, 'character_race_class_couplings');
    }

    /**
     * @return HasMany
     */
    public function specializations(): HasMany
    {
        return $this->hasMany(CharacterClass::class);
    }

    /**
     * @return BelongsTo
     */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    /**
     * @return HasMany
     */
    public function dungeonrouteplayerrace(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerRace::class);
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
