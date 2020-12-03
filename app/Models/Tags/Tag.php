<?php

namespace App\Models\Tags;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class Tag extends Model
{
    protected $fillable = ['name'];

    protected $visible = ['name'];

    /**
     * Gets all derived enemies from this Npc.
     *
     * @return HasMany
     */
    function models()
    {
        return $this->hasMany('App\Models\Tags\TagModel');
    }
}
