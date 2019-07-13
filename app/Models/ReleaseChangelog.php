<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $release_id
 * @property string $description
 *
 * @property Release $release
 * @property Collection $changes
 *
 * @mixin \Eloquent
 */
class ReleaseChangelog extends Model
{
    protected $fillable = ['id', 'release_id', 'description'];
    protected $with = ['changes'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function release()
    {
        return $this->hasOne('App\Models\Release');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function changes()
    {
        return $this->hasMany('App\Models\ReleaseChangelogChange')->orderBy('release_changelog_category_id');
    }
}
