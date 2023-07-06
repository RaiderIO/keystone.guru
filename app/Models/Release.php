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
 * @property string $title
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
    /**
     * @var int https://discord.com/developers/docs/resources/channel#embed-object-embed-limits
     */
    private const DISCORD_EMBED_DESCRIPTION_LIMIT = 4096;

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
    public function changelog(): HasOne
    {
        return $this->hasOne(ReleaseChangelog::class);
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
        $body       = trim(view('app.release.discord', [
            'model'   => $this,
            'mention' => $this->isMajorUpgrade(),
        ])->render());
        $bodyLength = strlen($body);

        $footer       = trim(view('app.release.discord_footer', [
            'homeUrl'      => route('home'),
            'changelogUrl' => route('misc.changelog'),
            'affixesUrl'   => route('misc.affixes'),
            'newRouteUrl'  => route('dungeonroute.new'),
            'patreonUrl'   => 'https://www.patreon.com/keystoneguru',
        ])->render());
        $footerLength = strlen($footer);

        // Subtract additional characters to account for the strings added below, to make sure the footer doesn't get cut into
        $truncatedBody       = substr($body, 0, self::DISCORD_EMBED_DESCRIPTION_LIMIT - 50 - $footerLength);
        $truncatedBodyLength = strlen($truncatedBody);

        if ($bodyLength !== $truncatedBodyLength) {
            $result = sprintf('%s (%d more) \n\n %s', $truncatedBody, $bodyLength - $truncatedBodyLength, $footer);
        } else {
            $result = sprintf('%s\n\n%s', $truncatedBody, $footer);
        }

        return $result;
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function getRedditBodyAttribute(): string
    {
        return trim(view('app.release.reddit', ['model' => $this])->render());
    }

    /**
     * Get the title formatted with the current date etc.
     */
    public function getFormattedTitle(): string
    {
        return sprintf('Release %s (%s)%s',
            $this->version, now()->format('Y/m/d'),
            empty($this->title) ? '' : sprintf(' - %s', $this->title));
    }

    /**
     * @return array
     */
    public function getDiscordEmbeds(): array
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
                'title'       => $this->getFormattedTitle(),
                'description' => substr($this->discord_body, 0, self::DISCORD_EMBED_DESCRIPTION_LIMIT),
                'url'         => sprintf('%s/release/%s', config('app.url'), $this->version),
                'timestamp'   => Carbon::now()->toIso8601String(),
                'footer'      => [
                    'icon_url' => 'https://keystone.guru/images/external/discord/footer_image.png',
                    'text'     => 'Keystone.guru Discord Bot',
                ],
            ],
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
    public function isMajorUpgrade(): bool
    {
        return $this->id === 1 || $this->_getPreviousRelease()->getSymVer()->getMajor() < $this->getSymVer()->getMajor();
    }

    /**
     * Checks if the release is a minor upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isMinorUpgrade(): bool
    {
        return $this->id === 1 || $this->_getPreviousRelease()->getSymVer()->getMinor() < $this->getSymVer()->getMinor();
    }

    /**
     * Checks if the release is a bugfix upgrade over the previous version.
     * @return bool
     * @throws InvalidVersionException
     */
    public function isBugfixUpgrade(): bool
    {
        return $this->id === 1 || $this->_getPreviousRelease()->getSymVer()->getPatch() < $this->getSymVer()->getPatch();
    }
}
