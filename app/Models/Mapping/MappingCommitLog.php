<?php

namespace App\Models\Mapping;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property bool   $merged
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class MappingCommitLog extends Model
{
    use SeederModel;

    protected $fillable = [
        'id',
        'merged',
        'updated_at',
        'created_at',
    ];
}
