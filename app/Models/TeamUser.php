<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $team_id
 * @property int    $user_id
 * @property string $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Team   $team
 * @property User   $user
 *
 * @method static Builder isModerator(int $userId)
 *
 * @mixin Eloquent
 */
class TeamUser extends Model
{
    public const ROLE_MEMBER = 'member';

    public const ROLE_COLLABORATOR = 'collaborator';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_ADMIN = 'admin';

    public const ALL_ROLES = [
        self::ROLE_MEMBER       => 1,
        self::ROLE_COLLABORATOR => 2,
        self::ROLE_MODERATOR    => 3,
        self::ROLE_ADMIN        => 4,
    ];

    protected $fillable = ['team_id', 'user_id', 'role'];

    protected $with = ['user'];

    public function scopeIsModerator(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->whereIn('role', [self::ROLE_ADMIN, self::ROLE_MODERATOR]);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
