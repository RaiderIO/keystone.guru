<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $release_id
 * @property string $description
 *
 * @property Release $release
 * @property ReleaseChangelogChange[]|Collection $changes
 *
 * @mixin Eloquent
 */
class ReleaseChangelog extends CacheModel
{
    protected $fillable = ['id', 'release_id', 'description'];
    protected $with = ['changes'];

    public $timestamps = false;

    /**
     * @return HasOne
     */
    public function release(): HasOne
    {
        return $this->hasOne(Release::class);
    }

    /**
     * @return HasMany
     */
    public function changes(): HasMany
    {
        return $this->hasMany(ReleaseChangelogChange::class)->orderBy('release_changelog_category_id');
    }
}
