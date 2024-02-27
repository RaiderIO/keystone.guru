<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use App\Models\ReleaseReportLog;
use App\Service\Discord\DiscordApiService;
use App\Service\Reddit\RedditApiService;
use Exception;
use Illuminate\Console\Command;

class ReportRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:release {platform} {version=latest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reports the creation of a release on Keystone.guru on various platforms';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(DiscordApiService $discordApiService, RedditApiService $redditApiService): void
    {
        $result   = false;
        $version  = $this->argument('version');
        $platform = $this->argument('platform');

        /** @var Release $release */
        if ($version === 'latest') {
            $release = Release::latest()->first();
        } else {
            if (!str_starts_with($version, 'v')) {
                $version = sprintf('v%s', $version);
            }

            $release = Release::where('version', $version)->first();
        }

        if ($release->silent) {
            $this->info('Not reporting release; it was marked as silent!');
            // Not failed if not necessary
            $result = true;
        } else if (config('app.type') === 'local' ||
            ReleaseReportLog::where('release_id', $release->id)
                ->where('platform', $platform)
                ->doesntExist()
        ) {
            $result = match ($platform) {
                'reddit' => $redditApiService->createPost(
                    config('keystoneguru.reddit_subreddit'),
                    $release->getFormattedTitle(),
                    $release->reddit_body
                ),
                'discord' => $discordApiService->sendEmbeds(config('keystoneguru.webhook.discord.new_release.url'), $release->getDiscordEmbeds()),
                default => throw new Exception(sprintf('Unsupported platform %s', $platform)),
            };

            // Log this release so that we don't mention things multiple times
            ReleaseReportLog::create([
                'release_id' => $release->id,
                'platform'   => $platform,
            ]);
        } else {
            $this->info('Not reporting release; it was already reported in the platform!');
            // Not failed if we already did it
            $result = true;
        }

        // If failed, return failed exit code
        if (!$result) {
            exit(1);
        }
    }
}
