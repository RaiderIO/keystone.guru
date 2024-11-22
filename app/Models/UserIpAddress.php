<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $ip_address
 * @property int    $count
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User   $user
 *
 * @mixin Eloquent
 */
class UserIpAddress extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'count',
        'updated_at',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
