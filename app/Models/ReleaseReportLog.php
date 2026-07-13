<?php

namespace App\Models;

use Eloquent;

/**
 * @property int    $id
 * @property string $version
 * @property string $platform
 * @property string $updated_at
 * @property string $created_at
 *
 * @mixin Eloquent
 */
class ReleaseReportLog extends CacheModel
{
    protected $fillable = [
        'version',
        'platform',
    ];
}
