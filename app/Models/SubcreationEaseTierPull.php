<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $current_affixes
 * @property string $source_url
 *
 * @property Carbon $last_updated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 **/
class SubcreationEaseTierPull extends Model
{
    protected $fillable = [
        'current_affixes',
        'source_url',
        'last_updated_at'
    ];
}
