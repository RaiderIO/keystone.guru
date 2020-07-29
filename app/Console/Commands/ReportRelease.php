<?php

namespace App\Console\Commands;

use App\Models\Release;
use App\Service\Discord\DiscordApiService;
use Github\Api\Repo;
use Github\Exception\MissingArgumentException;
use GrahamCampbell\GitHub\Facades\GitHub;
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
     * @return void
     * @throws \Exception
     */
    public function handle(DiscordApiService $discordApiService)
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
                break;
            case 'discord':
                $discordApiService->sendMessage(env('DISCORD_NEW_RELEASE_WEBHOOK'), $release->discord_body);
                break;
            default:
                throw new \Exception(sprintf('Unsupport platform %s', $platform));
        }
    }
}
