<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $release_id int
 * @property $platform string
 *
 * @property $updated_at string
 * @property $created_at string
 *
 * @mixin Eloquent
 */
class ReleaseReportLog extends CacheModel
{
    protected $fillable = ['release_id', 'platform'];

    /**
     * @return BelongsTo
     */
    function release()
    {
        return $this->belongsTo('App\Models\Release');
    }
}
