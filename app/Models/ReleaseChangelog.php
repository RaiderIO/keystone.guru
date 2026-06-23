<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int                                             $id
 * @property int                                             $release_id
 * @property string                                          $description
 * @property Release                                         $release
 * @property EloquentCollection<int, ReleaseChangelogChange> $changes
 *
 * @mixin Eloquent
 */
class ReleaseChangelog extends CacheModel
{
    use SeederModel;

    protected $fillable = [
        'id',
        'release_id',
        'description',
    ];

    protected $with = ['changes'];

    public $timestamps = false;

    /** @return HasOne<Release, $this> */
    public function release(): HasOne
    {
        return $this->hasOne(Release::class);
    }

    /** @return HasMany<ReleaseChangelogChange, $this> */
    public function changes(): HasMany
    {
        return $this->hasMany(ReleaseChangelogChange::class)->orderBy('release_changelog_category_id');
    }
}
