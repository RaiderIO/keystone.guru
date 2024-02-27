<?php

namespace App\Models;

use App\Models\Traits\HasGenericModelRelation;
use App\Models\User;
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
 * @property bool $contact_ok
 * @property string $status
 * @property User $author
 *
 * @mixin Eloquent
 */
class UserReport extends Model
{
    use HasGenericModelRelation;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
