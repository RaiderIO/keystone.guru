<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $release_changelog_id
 * @property string $version
 * @property $updated_at string
 * @property $created_at string
 *
 * @property ReleaseChangelog $changelog
 *
 * @mixin \Eloquent
 */
class Release extends Model
{
    public $timestamps = true;

    protected $with = ['changelog'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function changelog()
    {
        return $this->hasOne('App\Models\ReleaseChangelog');
    }
}
