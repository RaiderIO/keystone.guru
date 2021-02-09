<?php

namespace App\Models;

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
 * @mixin \Eloquent
 */
class ReleaseChangelogChange extends CacheModel
{
    protected $visible = ['ticket_id', 'change', 'category', 'release_changelog_category_id', 'release_changelog_id'];
    protected $with = ['category'];
    public $timestamps = false;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function category()
    {
        return $this->belongsTo('App\Models\ReleaseChangelogCategory', 'release_changelog_category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function changelog()
    {
        return $this->hasOne('App\Models\ReleaseChangelog');
    }
}
