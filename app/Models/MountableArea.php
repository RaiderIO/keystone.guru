<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $vertices_json
 *
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class MountableArea extends CacheModel
{
    public $timestamps = false;
    public $fillable = [
        'floor_id',
        'vertices_json',
    ];

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo('App\Models\Floor');
    }
}
