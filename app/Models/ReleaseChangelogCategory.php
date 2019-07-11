<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $category
 *
 * @mixin \Eloquent
 */
class ReleaseChangelogCategory extends Model
{
    public $timestamps = false;
    protected $fillable = ['category'];
}
