<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $author_id
 * @property string $context
 * @property string $category
 * @property string $username For anonymous users
 * @property string $message
 * @property \App\User $author
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
