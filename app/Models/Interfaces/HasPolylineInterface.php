<?php

namespace App\Models\Interfaces;

use App\Models\Floor\Floor;
use App\Models\Polyline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int|null      $polyline_id
 * @property int|null      $floor_id
 * @property Polyline|null $polyline
 * @property Floor|null    $floor
 *
 * @mixin Model
 */
interface HasPolylineInterface
{
    /** @return HasOne<Polyline, covariant Model> */
    public function polyline(): HasOne;
}
