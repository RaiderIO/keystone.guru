<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property string $key
 * @property string $name
 * @property string $color
 *
 * @property string $icon_url Appended
 *
 * @property EloquentCollection<int, CharacterRace> $races
 * @property EloquentCollection<int, DungeonRoute>  $dungeonRoutes
 *
 * @mixin Eloquent
 */
class Faction extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    public $hidden = [
        'pivot',
    ];

    public $fillable = [
        'id',
        'key',
        'name',
        'color',
    ];

    protected $appends = ['icon_url'];

    public const FACTION_ANY         = 'any';
    public const FACTION_UNSPECIFIED = 'unspecified';
    public const FACTION_HORDE       = 'horde';
    public const FACTION_ALLIANCE    = 'alliance';

    public const ALL = [
        self::FACTION_UNSPECIFIED => 1,
        self::FACTION_HORDE       => 2,
        self::FACTION_ALLIANCE    => 3,
    ];

    public function getIconUrlAttribute(): string
    {
        return ksgAssetImage(sprintf('factions/%s.png', $this->key));
    }

    /** @return HasMany<CharacterRace, $this> */
    public function races(): HasMany
    {
        return $this->hasMany(CharacterRace::class);
    }

    /** @return HasMany<DungeonRoute, $this> */
    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }
}
