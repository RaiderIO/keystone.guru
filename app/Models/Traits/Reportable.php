<?php

namespace App\Models\Traits;

use App\Models\UserReport;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property Collection|UserReport[] $userreports
 */
trait Reportable
{
    public function userreports(): HasMany
    {
        return $this->hasMany(UserReport::class, 'model_id')->where('model_class', $this::class);
    }
}
