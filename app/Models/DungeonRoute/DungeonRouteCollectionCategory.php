<?php

namespace App\Models\DungeonRoute;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The kind of routes a collection holds - what a viewer is signing up for when they open it.
 * Deliberately says nothing about key level: a "PUG friendly" collection is PUG friendly at any
 * key level.
 *
 * @property int    $id
 * @property string $name
 *
 * @property EloquentCollection<int, DungeonRouteCollection> $dungeonRouteCollections
 *
 * @mixin Eloquent
 */
class DungeonRouteCollectionCategory extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = ['pivot'];

    public const string PUG_FRIENDLY = 'pug_friendly';

    public const string BEGINNER = 'beginner';

    public const string INTERMEDIATE = 'intermediate';

    public const string EXPERT = 'expert';

    public const string MDI = 'mdi';

    public const array ALL = [
        self::PUG_FRIENDLY => 1,
        self::BEGINNER     => 2,
        self::INTERMEDIATE => 3,
        self::EXPERT       => 4,
        self::MDI          => 5,
    ];

    /** @return HasMany<DungeonRouteCollection, $this> */
    public function dungeonRouteCollections(): HasMany
    {
        return $this->hasMany(DungeonRouteCollection::class);
    }

    public function getTranslatedName(): string
    {
        return __(sprintf('dungeonroutecollectioncategories.%s', $this->name));
    }
}
