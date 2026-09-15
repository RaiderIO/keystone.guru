<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $version
 * @property string $platform
 * @property string $updated_at
 * @property string $created_at
 *
 * @mixin Eloquent
 */
class ReleaseReportLog extends Model
{
    protected $fillable = [
        'version',
        'platform',
    ];
}
