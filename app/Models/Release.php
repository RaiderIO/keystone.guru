<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $release_changelog_id
 * @property string $version
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property ReleaseChangelog $changelog
 *
 * @mixin \Eloquent
 */
class Release extends Model
{
    public $timestamps = true;

    protected $with = ['changelog'];

    public function getRouteKeyName()
    {
        return 'version';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function changelog()
    {
        return $this->hasOne('App\Models\ReleaseChangelog');
    }
}
