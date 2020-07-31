<?php

namespace App\Models;

use App\Models\Traits\SerializesDates;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $release_changelog_id
 * @property string $version
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property string $github_body
 * @property string $discord_body
 * @property string $reddit_body
 *
 * @property ReleaseChangelog $changelog
 *
 * @mixin \Eloquent
 */
class Release extends Model
{
    use SerializesDates;

    public $timestamps = true;

    protected $with = ['changelog'];
    protected $appends = ['github_body', 'discord_body', 'reddit_body'];

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

    /**
     * @return string
     * @throws \Throwable
     */
    public function getGithubBodyAttribute()
    {
        return trim(view('app.release.github', ['model' => $this])->render());
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function getDiscordBodyAttribute()
    {
        return trim(view('app.release.discord', ['model' => $this])->render());
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function getRedditBodyAttribute()
    {
        return trim(view('app.release.reddit', ['model' => $this])->render());
    }
}
