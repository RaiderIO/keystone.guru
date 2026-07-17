<?php

namespace App\Console\Commands\Release;

use Github\Api\Repo;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;

class Publish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:publish {tag}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes the draft GitHub Release for the given tag (takes it out of draft)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tag = $this->argument('tag');

        /** @var Repo $githubRepoClient */
        // @phpstan-ignore staticMethod.notFound
        $githubRepoClient = GitHub::repo();

        $owner      = config('keystoneguru.github_repository_owner');
        $repository = config('keystoneguru.github_repository');

        $githubRelease = null;
        foreach ($githubRepoClient->releases()->all($owner, $repository) as $release) {
            if ($release['tag_name'] === $tag) {
                $githubRelease = $release;
                break;
            }
        }

        if ($githubRelease === null) {
            $this->error(sprintf('Unable to find GitHub release %s', $tag));

            return self::FAILURE;
        }

        if (!$githubRelease['draft']) {
            $this->info(sprintf('GitHub release %s is already published; nothing to do.', $tag));

            return self::SUCCESS;
        }

        $githubRepoClient->releases()->edit($owner, $repository, $githubRelease['id'], ['draft' => false]);

        $this->info(sprintf('Published GitHub release %s.', $tag));

        return self::SUCCESS;
    }
}
