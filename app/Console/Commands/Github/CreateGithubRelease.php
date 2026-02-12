<?php

namespace App\Console\Commands\Github;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use Github\Api\Repo;
use Github\Exception\MissingArgumentException;
use Github\Exception\ValidationFailedException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;
use Throwable;

class CreateGithubRelease extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:githubrelease {version?} {--hash=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new release of Keystone.guru';

    /**
     * Execute the console command.
     *
     *
     * @throws MissingArgumentException
     * @throws Throwable
     */
    public function handle(
        ReleaseRepositoryInterface $releaseRepository,
    ): void {
        $version = $this->argument('version');
        $hash    = $this->option('hash');
        $release = $releaseRepository->findReleaseByVersion($version);

        if ($release !== null) {
            $this->info(sprintf('>> Creating Github release for %s', $release->version));

            $repositoryOwner = config('keystoneguru.github_repository_owner');
            $repository      = config('keystoneguru.github_repository');

            /** @var Repo $githubRepoClient */
            $githubRepoClient = GitHub::repo();
            // May throw an exception if it doesn't exist
            foreach ($githubRepoClient->releases()->all($repositoryOwner, $repository) as $githubRelease) {
                if ($githubRelease['name'] === $release->version) {
                    $this->error(sprintf('OK Unable to create release for %s; already exists!', $release->version));

                    return;
                }
            }

            $body = $release->getGithubBodyAttribute();

            try {
                $githubRepoClient->releases()->create($repositoryOwner, $repository, array_filter([
                    'tag_name'         => $release->version,
                    'name'             => $release->version,
                    'body'             => $body,
                    'target_commitish' => $hash,
                ]));

                $this->info(sprintf('Successfully created GitHub release %s', $release->version));
            } catch (ValidationFailedException $exception) {
                $this->warn(sprintf('Unable to create Github release for %s: %s', $release->version, $exception->getMessage()));
            }

            $this->info(sprintf('OK Creating Github release for %s', $release->version));
        } else {
            $this->error(sprintf('OK Unable to find release %s', $version));
        }
    }
}
