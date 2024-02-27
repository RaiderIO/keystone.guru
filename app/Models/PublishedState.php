<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Traits\SeederModel;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property Collection|DungeonRoute[] $dungeonRoutes
 *
 * @mixin Eloquent
 */
class PublishedState extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = ['pivot'];

    public const UNPUBLISHED = 'unpublished';

    public const TEAM = 'team';

    public const WORLD_WITH_LINK = 'world_with_link';

    public const WORLD = 'world';

    public const ALL = [
        self::UNPUBLISHED => 1,
        self::TEAM => 2,
        self::WORLD_WITH_LINK => 3,
        self::WORLD => 4,
    ];

    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    /**
     * @return Collection|string[]
     */
    public static function getAvailablePublishedStates(DungeonRoute $dungeonRoute, ?User $user = null): Collection
    {
        $result = new Collection();
        $result->push(PublishedState::UNPUBLISHED);
        $result->push(PublishedState::TEAM);

        if ($user !== null && $user->hasPatreonBenefit(PatreonBenefit::UNLISTED_ROUTES)) {
            $result->push(PublishedState::WORLD_WITH_LINK);
        }

        // Only active dungeons may be made public
        if ($dungeonRoute->dungeon->active) {
            $result->push(PublishedState::WORLD);
        }

        return $result;
    }
}
