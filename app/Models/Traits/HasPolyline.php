<?php

namespace App\Models\Traits;

use App\Models\Polyline;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int|null      $polyline_id
 * @property Polyline|null $polyline
 */
trait HasPolyline
{
    /** @return HasOne<Polyline, $this> */
    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')
            ->where('model_class', static::class);
    }
}
