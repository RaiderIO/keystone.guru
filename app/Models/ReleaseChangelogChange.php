<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int                      $id
 * @property int                      $release_changelog_id
 * @property int                      $release_changelog_category_id
 * @property int                      $ticket_id
 * @property string                   $change
 * @property bool                     $is_public
 * @property ReleaseChangelogCategory $category
 * @property ReleaseChangelog         $changelog
 *
 *
 * @mixin Eloquent
 */
class ReleaseChangelogChange extends CacheModel
{
    use SeederModel;

    protected $fillable = [
        'id',
        'release_changelog_id',
        'release_changelog_category_id',
        'ticket_id',
        'change',
        'is_public',
    ];

    protected $visible = [
        'ticket_id',
        'change',
        'release_changelog_category_id',
        'release_changelog_id',
        'is_public',
    ];

    protected $with = ['category'];

    public $timestamps = false;

    /** @return BelongsTo<ReleaseChangelogCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ReleaseChangelogCategory::class, 'release_changelog_category_id');
    }

    /** @return HasOne<ReleaseChangelog, $this> */
    public function changelog(): HasOne
    {
        return $this->hasOne(ReleaseChangelog::class, 'release_changelog_id');
    }

    /**
     * @param  EloquentBuilder<ReleaseChangelogChange> $query
     * @return EloquentBuilder<ReleaseChangelogChange>
     */
    public function scopePublic(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('is_public', true);
    }
}
