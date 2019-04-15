<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $author_id
 * @property string $context
 * @property string $category
 * @property string $username For anonymous users
 * @property string $message
 *
 * @property User $author
 *
 * @mixin \Eloquent
 */
class UserReport extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function author()
    {
        return $this->belongsTo('App\User', 'author_id');
    }
}
