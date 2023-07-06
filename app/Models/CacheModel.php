<?php


namespace App\Models;

use Eloquent;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Eloquent
 */
class CacheModel extends Model
{
    use Cachable;
}
