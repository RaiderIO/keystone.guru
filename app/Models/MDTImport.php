<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int          $id
 * @property int|null     $dungeon_route_id
 * @property string|null  $error
 * @property string       $import_string
 *
 * @property DungeonRoute $dungeonRoute
 *
 * @mixin Eloquent
 */
class MDTImport extends Model
{
    /** @var string Prevent MDT being translated to m_d_t */
    protected $table = 'mdt_imports';

    protected $fillable = [
        'dungeon_route_id',
        'error',
        'import_string',
    ];

    /**
     * Get the dungeon route that this import created.
     */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }
}
