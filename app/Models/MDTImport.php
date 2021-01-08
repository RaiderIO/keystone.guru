<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property string $import_string
 *
 * @mixin Eloquent
 */
class MDTImport extends Model
{
    /** @var string Prevent MDT being translated to m_d_t */
    protected $table = 'mdt_imports';

    /**
     * Get the dungeon route that this import created.
     *
     * @return BelongsTo
     */
    function dungeonroute()
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
