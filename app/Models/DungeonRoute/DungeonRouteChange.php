<?php

namespace App\Models\DungeonRoute;

use App\Models\Team;
use App\Models\Traits\HasGenericModelRelation;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int        $id
 * @property int        $dungeon_route_id
 * @property int|null   $user_id
 * @property int|null   $team_id
 * @property int|null   $team_role
 * @property int        $model_id
 * @property string     $model_class
 * @property array|null $before
 * @property array|null $after
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin Eloquent
 */
class DungeonRouteChange extends Model
{
    use HasGenericModelRelation;

    protected $fillable = [
        'dungeon_route_id',
        'user_id',
        'team_id',
        'team_role',
        'model_id',
        'model_class',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
