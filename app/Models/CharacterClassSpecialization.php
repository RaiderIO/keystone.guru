<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property string $name
 * @property int $character_class_id
 * @property int $icon_file_id
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterClassSpecialization extends CacheModel
{
    use HasIconFile;

    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];

    /**
     * @return BelongsTo
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
