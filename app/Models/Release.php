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
 * @property boolean $silent
 * @property boolean $spotlight
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
class Release extends CacheModel
{
    use SerializesDates;

    protected $with = ['changelog'];
    protected $appends = ['github_body', 'discord_body', 'reddit_body'];

    /**
     * @return Release|Model
     */
    private function _getPreviousRelease()
    {
        return Release::where('id', '<', $this->id)->orderBy('id', 'desc')->first();
    }

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
        $body = trim(view('app.release.discord', [
            'model'   => $this,
            'mention' => $this->isMajorUpgrade(),
        ])->render());
        $bodyLength = strlen($body);

        $footer = trim(view('app.release.discord_footer', [
            'homeUrl'      => route('home'),
            'changelogUrl' => route('misc.changelog'),
            'affixesUrl'   => route('misc.affixes'),
            'newRouteUrl'  => route('dungeonroute.new'),
            'patreonUrl'   => 'https://www.patreon.com/keystoneguru',
        ])->render());
        $footerLength = strlen($footer);

        // 2000 is the limit, but give it some additional padding just in case
        $truncatedBody = substr($body, 0, 1990 - $footerLength);
        $truncatedBodyLength = strlen($truncatedBody);

        if ($bodyLength !== $truncatedBodyLength) {
            $result = sprintf('%s (%d characters truncated) \n\n %s', $truncatedBody, $bodyLength - $truncatedBodyLength, $footer);
        } else {
            $result = sprintf('%s\n\n%s', $truncatedBody, $footer);
        }

        return $result;
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

        // Quick fix to get the release body to always show up in the #a
        // https://discord.com/developers/docs/resources/channel#embed-limits limit is 2048 characters
        $discordBody = $this->discord_body;
        $truncatedDiscordBody = substr($discordBody, 0, 2000);

        if (strlen($discordBody) !== strlen($truncatedDiscordBody)) {
            $discordBody = sprintf('%s (%d characters truncated)', $truncatedDiscordBody, strlen($discordBody) - strlen($truncatedDiscordBody));
        }

        return [
            [
                'color'       => 14641434, // '#DF691A'
                'title'       => sprintf('Release %s (%s)', $this->version, $this->created_at->format('Y/m/d')),
                'description' => $discordBody,
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
     * @return \PHLAK\SemVer\Version
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
        return $this->id === 1 ? true : $this->_getPreviousRelease()->getSymVer()->getMajor() < $this->getSymVer()->getMajor();
    }

    /**
     * Checks if the release is a minor upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isMinorUpgrade()
    {
        return $this->id === 1 ? true : $this->_getPreviousRelease()->getSymVer()->getMinor() < $this->getSymVer()->getMinor();
    }

    /**
     * Checks if the release is a bugfix upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isBugfixUpgrade()
    {
        return $this->id === 1 ? true : $this->_getPreviousRelease()->getSymVer()->getPatch() < $this->getSymVer()->getPatch();
    }
}
