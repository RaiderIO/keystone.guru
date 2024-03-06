<?php

namespace App\Models\Traits;

use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait SeederModel
{
    public static function boot(): void
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(static fn(Model $model) => $model instanceof MappingVersion || $model instanceof Floor || $model instanceof Enemy);
    }
}
