<?php

namespace App\Console\Commands\Github;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Github\Api\Repo;
use Github\Exception\MissingArgumentException;
use Github\Exception\ValidationFailedException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Throwable;

class CreateGithubRelease extends GithubReleaseCommand
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
     * @throws Throwable
     */
    public function handle()
    {
        $version = $this->argument('version');
        $release = $this->findReleaseByVersion($version);

        if ($release !== null) {
            $this->info(sprintf('>> Creating Github release for %s', $release->version));

            $username   = config('keystoneguru.github_username');
            $repository = config('keystoneguru.github_repository');

            /** @var Repo $githubRepoClient */
            $githubRepoClient = GitHub::repo();
            // May throw an exception if it doesn't exist
            foreach ($githubRepoClient->releases()->all($username, $repository) as $githubRelease) {
                if ($githubRelease['name'] === $release->version) {
                    $this->error(sprintf('OK Unable to create release for %s; already exists!', $release->version));
                    return;
                }
            }

            $body = $release->getGithubBodyAttribute();

            try {
                $githubRepoClient->releases()->create($username, $repository, [
                    'tag_name' => $release->version,
                    'name'     => $release->version,
                    'body'     => $body,
                ]);
                $this->info(sprintf('Successfully created GitHub release %s', $release->version));

                // Fetch the created version from Github so we can use it later on
                $this->shell('git fetch');
            } catch (ValidationFailedException $exception) {
                $this->warn(sprintf('Unable to create Github release for %s: %s', $release->version, $exception->getMessage()));
            }

            $this->info(sprintf('OK Creating Github release for %s', $release->version));
        } else {
            $this->error(sprintf('OK Unable to find release %s', $version));
        }
    }
}
