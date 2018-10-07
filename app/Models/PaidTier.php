<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $name string
 */
class PaidTier extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name'
    ];

}
