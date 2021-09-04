<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use Illuminate\Console\Command;

class GetReleaseBody extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keystoneguru:release {version} {platform=github}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves the latest/current release of Keystone.guru';

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
     * @return void
     */
    public function handle()
    {
        $version  = $this->argument('version');
        $platform = $this->argument('platform');

        if (substr($version, 0, 1) !== 'v') {
            $version = 'v' . $version;
        }

        /** @var Release $release */
        $release = Release::where('version', $version)->first();

        switch ($platform) {
            case 'github':
                $this->line($release->github_body);
                break;
            case 'reddit':
                $this->line($release->reddit_body);
                break;
            case 'discord':
                $this->line($release->discord_body);
                break;
            default:
                throw new \Exception(sprintf('Unsupport platform %s', $platform));
        }
    }
}
