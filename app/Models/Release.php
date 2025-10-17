<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use App\Models\Traits\SerializesDates;
use App\Vendor\SemVer\Version;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use Throwable;

/**
 * @property int    $id
 * @property int    $release_changelog_id
 * @property string $version
 * @property string $title
 * @property bool   $backup_db
 * @property bool   $silent
 * @property bool   $spotlight
 * @property bool   $released             A per-environment flag to indicate if the release has been released to the public
 *
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
    use SeederModel;
    use SerializesDates;

    protected $fillable = [
        'id',
        'release_changelog_id',
        'version',
        'title',
        'backup_db',
        'silent',
        'spotlight',
        'released',
        'created_at',
        'updated_at',
    ];

    protected $with = ['changelog'];

    protected $appends = [
        'github_body',
        'discord_body',
        'reddit_body',
    ];

    protected $hidden = [
        'reddit_body',
        'discord_body',
        'github_body',
    ];

    /**
     * @var int https://discord.com/developers/docs/resources/channel#embed-object-embed-limits
     */
    private const DISCORD_EMBED_DESCRIPTION_LIMIT = 4096;

    private function getPreviousRelease(): ?Release
    {
        return Release::where('id', '<', $this->id)->orderBy('id', 'desc')->first();
    }

    public function getRouteKeyName(): string
    {
        return 'version';
    }

    public function changelog(): HasOne
    {
        return $this->hasOne(ReleaseChangelog::class);
    }

    /**
     * @throws Throwable
     */
    public function getGithubBodyAttribute(): string
    {
        return trim(view('app.release.github', ['model' => $this])->render());
    }

    /**
     * @throws Throwable
     */
    public function getDiscordBodyAttribute(): string
    {
        $body = trim(view('app.release.discord', [
            'model'   => $this,
            'mention' => !$this->silent && $this->isMajorUpgrade(),
        ])->render());
        $bodyLength = strlen($body);

        $footer = trim(view('app.release.discord_footer', [
            'homeUrl'      => $this->publicRoute('home'),
            'changelogUrl' => $this->publicRoute('misc.changelog'),
            'affixesUrl'   => $this->publicRoute('misc.affixes'),
            'newRouteUrl'  => $this->publicRoute('dungeonroute.new'),
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
        return sprintf(
            'Release %s (%s)%s',
            $this->version,
            $this->created_at->format('Y/m/d'),
            empty($this->title) ? '' : sprintf(' - %s', $this->title),
        );
    }

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
                'color' => 14641434,
                // '#DF691A'
                'title'       => $this->getFormattedTitle(),
                'description' => substr($this->discord_body, 0, self::DISCORD_EMBED_DESCRIPTION_LIMIT),
                'url'         => sprintf('%s/release/%s', config('app.url'), $this->version),
                'timestamp'   => Carbon::now()->toIso8601String(),
                'footer'      => [
                    'icon_url' => ksgAssetImage('external/discord/footer_image.png'),
                    'text'     => 'Keystone.guru Discord Bot',
                ],
            ],
        ];
    }

    /**
     * @throws InvalidVersionException
     */
    public function getSymVer(): \PHLAK\SemVer\Version
    {
        return Version::parse($this->version);
    }

    /**
     * Checks if the release is a major upgrade over the previous version.
     *
     * @throws InvalidVersionException
     */
    public function isMajorUpgrade(): bool
    {
        return $this->id === 1 || $this->getPreviousRelease()->getSymVer()->getMajor() < $this->getSymVer()->getMajor();
    }

    /**
     * Checks if the release is a minor upgrade over the previous version.
     *
     * @throws InvalidVersionException
     */
    public function isMinorUpgrade(): bool
    {
        return $this->id === 1 || $this->getPreviousRelease()->getSymVer()->getMinor() < $this->getSymVer()->getMinor();
    }

    /**
     * Checks if the release is a bugfix upgrade over the previous version.
     *
     * @throws InvalidVersionException
     */
    public function isBugfixUpgrade(): bool
    {
        return $this->id === 1 || $this->getPreviousRelease()->getSymVer()->getPatch() < $this->getSymVer()->getPatch();
    }

    private function publicRoute(string $name, array $params = [], string $host = 'https://keystone.guru'): string
    {
        return rtrim($host, '/') . route($name, $params, false);
    }
}
