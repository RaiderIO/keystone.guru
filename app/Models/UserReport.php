<?php

namespace App\Models;

use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int          $id
 * @property int          $model_id
 * @property class-string $model_class
 * @property int          $user_id
 * @property string       $username    For anonymous users
 * @property string       $category
 * @property string       $message
 * @property bool         $contact_ok
 * @property string       $status
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User $user
 *
 * @mixin Eloquent
 */
class UserReport extends Model
{
    use HasGenericModelRelation;

    protected $fillable = [
        'id',
        'model_id',
        'model_class',
        'user_id',
        'username',
        'category',
        'message',
        'contact_ok',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
