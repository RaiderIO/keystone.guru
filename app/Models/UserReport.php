<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $model_id
 * @property int $model_class
 * @property int $user_id
 * @property string $username For anonymous users
 * @property string $category
 * @property string $message
 * @property boolean $contact_ok
 * @property string $status
 *
 * @property User $author
 *
 * @mixin Eloquent
 */
class UserReport extends Model
{
    /**
     * @return BelongsTo
     */
    function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
