<?php

namespace App\Console\Commands;

use App\Models\Release;
use App\Service\Discord\DiscordApiService;
use App\Service\Reddit\RedditApiService;
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
     * @param DiscordApiService $discordApiService
     * @param RedditApiService $redditApiService
     * @return void
     * @throws \Exception
     */
    public function handle(DiscordApiService $discordApiService, RedditApiService $redditApiService)
    {
        $version = $this->argument('version');
        $platform = $this->argument('platform');

        /** @var Release $release */
        if ($version === 'latest') {
            $release = Release::latest()->first();
        } else {
            if (substr($version, 0, 1) !== 'v') {
                $version = 'v' . $version;
            }
            $release = Release::where('version', $version)->first();
        }

        switch ($platform) {
            case 'reddit':
                $result = $redditApiService->createPost(
                    config('keystoneguru.reddit_subreddit'),
                    sprintf('%s (%s)', $release->version, $release->created_at->format('Y/M/d')),
                    $release->reddit_body
                );
                break;
            case 'discord':
                $result = $discordApiService->sendMessage(env('DISCORD_NEW_RELEASE_WEBHOOK'), $release->discord_body);
                break;
            default:
                throw new \Exception(sprintf('Unsupport platform %s', $platform));
        }

        // If failed, return failed exit code
        if (!$result) {
            exit(1);
        }
    }
}
