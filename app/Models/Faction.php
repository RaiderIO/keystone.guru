<?php

namespace App\Models;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $races
 * @property \Illuminate\Support\Collection $dungeonroutes
 */
class Faction extends IconFileModel
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function races()
    {
        return $this->hasMany('App\Models\CharacterRace');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
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
