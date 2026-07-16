<?php

namespace App\Models;

use Database\Factories\BannedIpAddressFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $ip_address
 * @property string|null $reason
 * @property Carbon|null $expires_at
 * @property int         $created_by
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property User $createdBy
 *
 * @mixin Eloquent
 */
class BannedIpAddress extends Model
{
    /** @use HasFactory<BannedIpAddressFactory> */
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
