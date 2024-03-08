<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int                      $id
 * @property int                      $release_changelog_id
 * @property int                      $release_changelog_category_id
 * @property int                      $ticket_id
 * @property string                   $change
 * @property ReleaseChangelogCategory $category
 * @property ReleaseChangelog         $changelog
 *
 * @mixin Eloquent
 */
class ReleaseChangelogChange extends CacheModel
{
    use SeederModel;

    protected $fillable = ['id', 'release_changelog_id', 'release_changelog_category_id', 'ticket_id', 'change'];

    protected $visible = ['ticket_id', 'change', 'release_changelog_category_id', 'release_changelog_id'];

    protected $with = ['category'];

    public $timestamps = false;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReleaseChangelogCategory::class, 'release_changelog_category_id');
    }

    public function changelog(): HasOne
    {
        return $this->hasOne(ReleaseChangelog::class, 'release_changelog_id');
    }
}
