<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $release_changelog_id
 * @property int $release_changelog_category_id
 * @property int $ticket_id
 * @property string $change
 *
 * @property ReleaseChangelogCategory $category
 * @property ReleaseChangelog $changelog
 *
 * @mixin Eloquent
 */
class ReleaseChangelogChange extends CacheModel
{
    protected $visible = ['ticket_id', 'change', 'category', 'release_changelog_category_id', 'release_changelog_id'];
    protected $with = ['category'];
    public $timestamps = false;


    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ReleaseChangelogCategory::class, 'release_changelog_category_id');
    }

    /**
     * @return HasOne
     */
    public function changelog(): HasOne
    {
        return $this->hasOne(ReleaseChangelog::class);
    }
}
