<?php

namespace App\Models\Mapping;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $dungeon_id
 * @property int $version
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class MappingVersion extends Model
{
    protected $fillable = [
        'dungeon_id',
        'version',
        'updated_at',
        'created_at',
    ];

    public $timestamps = true;
}
