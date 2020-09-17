<?php

namespace App\Models;

use App\Models\Traits\SerializesDates;
use App\Vendor\SemVer\Version;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use Throwable;

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
 * @mixin Eloquent
 */
class Release extends Model
{
    use SerializesDates;

    protected $with = ['changelog'];
    protected $appends = ['github_body', 'discord_body', 'reddit_body'];

    public function getRouteKeyName()
    {
        return 'version';
    }

    /**
     * @return HasOne
     */
    function changelog()
    {
        return $this->hasOne('App\Models\ReleaseChangelog');
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function getGithubBodyAttribute()
    {
        return trim(view('app.release.github', ['model' => $this])->render());
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function getDiscordBodyAttribute()
    {
        return trim(view('app.release.discord', [
            'model' => $this,
            'mention' => $this->isMajorUpgrade(),
            'homeUrl' => route('home'),
            'changelogUrl' => route('misc.changelog'),
            'affixesUrl' => route('misc.affixes'),
            'sandboxUrl' => route('dungeonroute.sandbox'),
            'patreonUrl' => 'https://www.patreon.com/keystoneguru',
        ])->render());
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function getRedditBodyAttribute()
    {
        return trim(view('app.release.reddit', ['model' => $this])->render());
    }

    /**
     * @return array
     */
    public function getDiscordEmbeds()
    {
//        $result = [];
//
//        $description = $this->changelog->description;
//
//        // Categories
//        foreach ($this->changelog->getRelation('changes')->groupBy('release_changelog_category_id') as $categoryId => $changes) {
//
//            /** @var ReleaseChangelogChange[] $changes */
//            $category = ReleaseChangelogCategory::findOrFail($categoryId);
//
//            $embed = [
//                'color'  => 14641434, // '#DF691A'
//                'title'  => $category->category,
//                'fields' => [],
//            ];
//
//            foreach($changes as $change){
//                $embed['fields'][] = [
//                    'name' => sprintf('#%s', $change->ticket_id),
//                    'value' => $change->change,
//                ];
//            }
//
//            $result[] = $embed;
//        }
//
//        // Footer
////        $result[] = [
////            'color'     => 14641434, // '#DF691A'
////            'timestamp' => Carbon::now()->toIso8601String(),
////            'footer'    => [
////                'text' => 'Keystone.guru Release Notifier'
////            ]
////        ];

        return [
            [
                'color'       => 14641434, // '#DF691A'
                'title'       => sprintf('Release %s (%s)', $this->version, $this->created_at->format('Y/m/d')),
                'description' => $this->discord_body,
                'url'         => sprintf('%s/release/%s', env('APP_URL'), $this->version),
                'timestamp'   => Carbon::now()->toIso8601String(),
                'footer'      => [
                    'icon_url' => 'https://keystone.guru/images/external/discord/footer_image.png',
                    'text'     => 'Keystone.guru Discord Bot'
                ],
            ]
        ];
    }

    /**
     * @return Version|\PHLAK\SemVer\Version
     * @throws InvalidVersionException
     */
    public function getSymVer()
    {
        return Version::parse($this->version);
    }

    /**
     * Checks if the release is a major upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isMajorUpgrade()
    {
        if ($this->id === 1) {
            $result = true;
        } else {
            $result = Release::findOrFail($this->id - 1)->getSymVer()->getMajor() < $this->getSymVer()->getMajor();
        }
        return $result;
    }

    /**
     * Checks if the release is a minor upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isMinorUpgrade()
    {
        if ($this->id === 1) {
            $result = true;
        } else {
            $result = Release::findOrFail($this->id - 1)->getSymVer()->getMinor() < $this->getSymVer()->getMinor();
        }
        return $result;
    }

    /**
     * Checks if the release is a bugfix upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isBugfixUpgrade()
    {
        if ($this->id === 1) {
            $result = true;
        } else {
            $result = Release::findOrFail($this->id - 1)->getSymVer()->getPatch() < $this->getSymVer()->getPatch();
        }
        return $result;
    }
}
