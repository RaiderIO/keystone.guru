<?php

namespace App\Console\Commands;

use App\Models\Release;
use Github\Api\Repo;
use Github\Exception\MissingArgumentException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;

class CreateGithubRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:githubrelease {version}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new release of Keystone.guru';

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

        if (substr($version, 0, 1) !== 'v') {
            $version = 'v' . $version;
        }

        /** @var Release $release */
        $release = Release::where('version', $version)->first();

        $username = config('keystoneguru.github_username');
        $repository = config('keystoneguru.github_repository');

        /** @var Repo $githubRepoClient */
        $githubRepoClient = GitHub::repo();
        // May throw an exception if it doesn't exist
        foreach ($githubRepoClient->releases()->all($username, $repository) as $githubRelease) {
            if ($githubRelease['name'] === $version) {
                $this->error(sprintf('Unable to create release for %s; already exists!', $version));
                return;
            }
        }

        $githubRepoClient->releases()->create($username, $repository, ['tag_name' => $release->version]);
        $this->info(sprintf('Successfully created GitHub release %s', $version));
    }
}
