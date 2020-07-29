<?php

namespace App\Console\Commands;

use App\Models\Release;
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
     * @return void
     * @throws MissingArgumentException
     */
    public function handle()
    {
        $version = $this->argument('version');
        $platform = $this->argument('platform');

        /** @var Release $release */
        if( $version === 'latest' ) {
            if (substr($version, 0, 1) !== 'v') {
                $version = 'v' . $version;
            }
            $release = Release::where('version', $version)->first();
        } else {
            $release = Release::latest()->get();
        }


        switch ($platform) {
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
