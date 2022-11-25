<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $release_id
 * @property string $platform
 *
 * @property string $updated_at
 * @property string $created_at
 *
 * @mixin Eloquent
 */
class ReleaseReportLog extends CacheModel
{
    protected $fillable = ['release_id', 'platform'];

    /**
     * @return BelongsTo
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
