<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $file_id
 * @property string $category
 * @property string $name
 * @property string $alias
 *
 * @property \App\Models\File $file
 *
 * @mixin \Eloquent
 */
class GameIcon extends Model
{
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function file()
    {
        return $this->belongsTo('App\Models\File');
    }
}
