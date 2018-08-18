<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor1_id
 * @property int $floor2_id
 * @property string $direction
 */
class FloorCoupling extends Model
{
    //
    public $timestamps = false;
}
