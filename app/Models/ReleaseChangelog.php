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
    function release()
    {
        return $this->hasOne('App\Models\Release');
    }

    /**
     * @return HasMany
     */
    function changes()
    {
        return $this->hasMany('App\Models\ReleaseChangelogChange')->orderBy('release_changelog_category_id');
    }
}
