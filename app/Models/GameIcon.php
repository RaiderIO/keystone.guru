<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $file_id
 * @property string $category
 * @property string $name
 * @property string $alias
 *
 * @property File $file
 *
 * @mixin Eloquent
 */
class GameIcon extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
