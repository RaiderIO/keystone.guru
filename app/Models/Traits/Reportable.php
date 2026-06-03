<?php

namespace App\Models\Traits;

use App\Models\UserReport;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EloquentCollection<int, UserReport> $userreports
 */
trait Reportable
{
    public function userreports(): HasMany
    {
        return $this->hasMany(UserReport::class, 'model_id')->where('model_class', $this::class);
    }
}
