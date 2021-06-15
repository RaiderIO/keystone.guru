<?php

namespace App\Models;

use App\Models\Traits\GeneratesPublicKey;
use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $dungeon_route_id
 * @property int $user_id
 * @property string $public_key
 *
 * @property User $user
 * @property DungeonRoute $dungeonroute
 *
 * @property Carbon $expires_at
 *
 * @mixin Eloquent
 */
class LiveSession extends Model
{
    protected $fillable = [
        'dungeon_route_id',
        'user_id',
        'public_key'
    ];

    protected $with = [
        'user',
        'dungeonroute'
    ];

    use GeneratesPublicKey;

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'public_key';
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the dungeon route that this killzone is attached to.
     *
     * @return BelongsTo
     */
    function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo('App\Models\DungeonRoute');
    }
}
