<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $release_changelog_id
 * @property int $release_category_id
 * @property int $ticket_id
 * @property string $change
 *
 * @mixin \Eloquent
 */
class ReleaseChangelogChange extends Model
{
    protected $visible = ['ticket_id', 'change', 'release_category_id', 'release_changelog_id'];
    protected $with = ['category'];
    public $timestamps = false;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function category()
    {
        return $this->belongsTo('App\Models\ReleaseChangelogCategory');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function changelog()
    {
        return $this->belongsTo('App\Models\Changelog');
    }
}
