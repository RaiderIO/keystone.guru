<?php

namespace App\Console\Commands\Release;

use App\Repositories\Interfaces\ReleaseReportLogRepositoryInterface;
use App\Service\Discord\DiscordApiServiceInterface;
use App\Vendor\SemVer\Version;
use Exception;
use Github\Api\Repo;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class Report extends Command
{
    /**
     * @var int https://discord.com/developers/docs/resources/channel#embed-object-embed-limits
     */
    private const int DISCORD_EMBED_DESCRIPTION_LIMIT = 4096;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:report {version=latest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Announces a GitHub Release of Keystone.guru on Discord';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(
        DiscordApiServiceInterface          $discordApiService,
        ReleaseReportLogRepositoryInterface $releaseReportLogRepository,
    ): int {
        $version = $this->argument('version');
        if ($version !== 'latest' && !str_starts_with($version, 'v')) {
            $version = sprintf('v%s', $version);
        }

        $githubReleases = $this->getGithubReleases();

        $releaseIndex = $version === 'latest' ? 0 : null;
        if ($releaseIndex === null) {
            foreach ($githubReleases as $index => $githubRelease) {
                if ($githubRelease['tag_name'] === $version) {
                    $releaseIndex = $index;
                    break;
                }
            }
        }

        $githubRelease = $releaseIndex !== null ? ($githubReleases[$releaseIndex] ?? null) : null;
        if ($githubRelease === null) {
            $this->error(sprintf('Unable to find GitHub release %s', $version));

            return self::FAILURE;
        }

        // A release without public changes has an empty GitHub Release body - nothing to announce
        $body = trim((string)($githubRelease['body'] ?? ''));
        if ($body === '') {
            $this->info('Not reporting release; it has no public changes (empty GitHub Release body)!');

            return self::SUCCESS;
        }

        if (config('app.type') !== 'local' &&
            $releaseReportLogRepository->hasReportedVersionOnPlatform($githubRelease['tag_name'], 'discord')) {
            $this->info('Not reporting release; it was already reported in the platform!');

            return self::SUCCESS;
        }

        $result = $discordApiService->sendEmbeds(
            config('keystoneguru.webhook.discord.new_release.url'),
            $this->getDiscordEmbeds($githubRelease, $githubReleases[$releaseIndex + 1] ?? null),
        );

        if (!$result) {
            return self::FAILURE;
        }

        // Log this release so that we don't mention things multiple times
        $releaseReportLogRepository->create([
            'version'  => $githubRelease['tag_name'],
            'platform' => 'discord',
        ]);

        return self::SUCCESS;
    }

    /**
     * Published (non-draft, non-prerelease) GitHub Releases, newest first. Only the first page - good enough.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getGithubReleases(): array
    {
        /** @var Repo $githubRepoClient */
        // @phpstan-ignore staticMethod.notFound
        $githubRepoClient = GitHub::repo();

        $githubReleases = $githubRepoClient->releases()->all(
            config('keystoneguru.github_repository_owner'),
            config('keystoneguru.github_repository'),
        );

        return array_values(array_filter(
            $githubReleases,
            static fn(array $githubRelease) => !$githubRelease['draft'] && !$githubRelease['prerelease'],
        ));
    }

    /**
     * @param array<string, mixed>      $githubRelease
     * @param array<string, mixed>|null $previousGithubRelease
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDiscordEmbeds(array $githubRelease, ?array $previousGithubRelease): array
    {
        $body = trim((string)$githubRelease['body']);
        // Only mention everyone for major upgrades
        if ($this->isMajorUpgrade($githubRelease, $previousGithubRelease)) {
            $body = sprintf("@everyone\n%s", $body);
        }

        $footer = trim(view('app.release.discord_footer', [
            'homeUrl'      => $this->publicRoute('home'),
            'changelogUrl' => sprintf(
                'https://github.com/%s/%s/releases',
                config('keystoneguru.github_repository_owner'),
                config('keystoneguru.github_repository'),
            ),
            'affixesUrl'  => $this->publicRoute('misc.affixes'),
            'newRouteUrl' => $this->publicRoute('dungeonroute.new'),
            'patreonUrl'  => 'https://www.patreon.com/keystoneguru',
        ])->render());
        $footerLength = strlen($footer);

        // Subtract additional characters to account for the strings added below, to make sure the footer doesn't get cut into
        $bodyLength          = strlen($body);
        $truncatedBody       = substr($body, 0, self::DISCORD_EMBED_DESCRIPTION_LIMIT - 50 - $footerLength);
        $truncatedBodyLength = strlen($truncatedBody);

        if ($bodyLength !== $truncatedBodyLength) {
            $description = sprintf("%s (%d more) \n\n %s", $truncatedBody, $bodyLength - $truncatedBodyLength, $footer);
        } else {
            $description = sprintf("%s\n\n%s", $truncatedBody, $footer);
        }

        return [
            [
                'color' => 14641434,
                // '#DF691A'
                'title'       => $this->getFormattedTitle($githubRelease),
                'description' => $description,
                'url'         => $githubRelease['html_url'],
                'timestamp'   => Carbon::now()->toIso8601String(),
                'footer'      => [
                    'icon_url' => ksgAssetImage('external/discord/footer_image.png'),
                    'text'     => 'Keystone.guru Discord Bot',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $githubRelease */
    private function getFormattedTitle(array $githubRelease): string
    {
        return sprintf(
            'Release %s (%s)',
            $githubRelease['tag_name'],
            Carbon::parse($githubRelease['published_at'])->format('Y/m/d'),
        );
    }

    /**
     * Checks if the release is a major upgrade over the previous version.
     *
     * @param array<string, mixed>      $githubRelease
     * @param array<string, mixed>|null $previousGithubRelease
     */
    private function isMajorUpgrade(array $githubRelease, ?array $previousGithubRelease): bool
    {
        if ($previousGithubRelease === null) {
            return true;
        }

        try {
            return Version::parse($previousGithubRelease['tag_name'])->getMajor() <
                Version::parse($githubRelease['tag_name'])->getMajor();
        } catch (Exception) {
            return false;
        }
    }

    /** @param array<string, mixed> $params */
    private function publicRoute(string $name, array $params = [], string $host = 'https://keystone.guru'): string
    {
        return rtrim($host, '/') . route($name, $params, false);
    }
}
