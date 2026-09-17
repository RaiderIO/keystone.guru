<?php

namespace App\Models\DungeonRoute;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
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
class DungeonRouteCollectionCategory extends Model
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = ['pivot'];

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
