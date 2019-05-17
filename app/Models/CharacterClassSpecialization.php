<?php

namespace App\Models;
/**
 * @property string $name
 * @property int $character_class_id
 * @property int $icon_file_id
 * @property \Illuminate\Support\Collection $specializations
 *
 * @mixin \Eloquent
 */
class CharacterClassSpecialization extends IconFileModel
{
    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function class()
    {
        return $this->belongsTo('App\Models\CharacterClass');
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
