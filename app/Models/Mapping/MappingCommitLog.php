<?php

namespace App\Models\Mapping;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property bool $merged
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class MappingCommitLog extends Model
{
    protected $fillable = [
        'id',
        'merged',
        'updated_at',
        'created_at'
    ];
}
