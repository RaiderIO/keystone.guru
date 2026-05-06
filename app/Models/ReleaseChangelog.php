<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int                                $id
 * @property int                                $release_id
 * @property string                             $description
 * @property Release                            $release
 * @property Collection<ReleaseChangelogChange> $changes
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

    public function release(): HasOne
    {
        return $this->hasOne(Release::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ReleaseChangelogChange::class)->orderBy('release_changelog_category_id');
    }
}
