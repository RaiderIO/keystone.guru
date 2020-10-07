<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Release;
use Github\Api\Repo;
use Github\Exception\MissingArgumentException;
use Github\Exception\ValidationFailedException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;

class CreateGithubRelease extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:githubrelease {version?}';

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
        $this->info(sprintf('>> Creating Github release for %s', $version));

        if ($version === null) {
            $release = Release::latest()->first();
        } else {
            if (substr($version, 0, 1) !== 'v') {
                $version = 'v' . $version;
            }

            /** @var Release $release */
            $release = Release::where('version', $version)->first();
        }

        if ($release !== null) {
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

            $body = $release->getGithubBodyAttribute();

            try {
                $githubRepoClient->releases()->create($username, $repository, [
                    'tag_name' => $release->version,
                    'name'     => $release->version,
                    'body'     => $body
                ]);
                $this->info(sprintf('Successfully created GitHub release %s', $version));

                // Fetch the created version from Github so we can use it later on
                $this->shell('git fetch');
            } catch (ValidationFailedException $exception) {
                $this->warn(sprintf('Unable to create Github release for %s: %s', $version, $exception->getMessage()));
            }

        } else {
            $this->error(sprintf('Unable to find release %s', $version));
        }

        $this->info(sprintf('OK Creating Github release for %s', $version));
    }
}
