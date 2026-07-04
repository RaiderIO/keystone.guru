<?php

namespace App\Console\Commands\Github;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use Github\Api\Issue;
use Github\Api\PullRequest;
use Github\Exception\MissingArgumentException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;

class CreateGithubReleasePullRequest extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:githubreleasepullrequest {version?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new pull request for a release on the Keystone.guru Github';

    /**
     * Execute the console command.
     *
     *
     * @throws MissingArgumentException
     */
    public function handle(
        ReleaseRepositoryInterface $releaseRepository,
    ): int {
        $version = $this->argument('version');
        $release = $releaseRepository->findReleaseByVersion($version);

        $this->info(sprintf('>> Creating Github pull request for %s', $release->version ?? 'the latest release'));

        if ($release === null) {
            $this->error(sprintf('Unable to find release %s', $version));

            return self::FAILURE;
        }

        $sourceBranch = 'development';
        $targetBranch = 'master';

        $username        = config('keystoneguru.github_username');
        $repositoryOwner = config('keystoneguru.github_repository_owner');
        $repository      = config('keystoneguru.github_repository');

        /** @var PullRequest $githubPullRequestClient */
        // @phpstan-ignore staticMethod.notFound
        $githubPullRequestClient = GitHub::pr();
        // May throw an exception if it doesn't exist
        $existingPullRequestId = 0;
        $pullRequestTitle      = sprintf('Release %s', $release->version);

        // This is a same-repo release PR, so instead of reconstructing and string-matching the
        // owner/repo, just confirm it's not a fork and check the branch refs. Case-insensitive
        // because the GitHub API normalizes repo full_names to lowercase.
        $expectedRepo = strtolower(sprintf('%s/%s', $repositoryOwner, $repository));

        // Only gets the first page - but good enough
        foreach ($githubPullRequestClient->all($repositoryOwner, $repository, [
            'state'  => 'open',
            'labels' => 'release',
        ]) as $githubPullRequest) {
            if (strtolower((string)$githubPullRequest['head']['repo']['full_name']) === $expectedRepo &&
                strtolower((string)$githubPullRequest['base']['repo']['full_name']) === $expectedRepo &&
                $githubPullRequest['head']['ref'] === $sourceBranch &&
                $githubPullRequest['base']['ref'] === $targetBranch) {
                $existingPullRequestId = $githubPullRequest['number'];
                break;
            }
        }

        // Append the release title here so that we don't match on it earlier
        $pullRequestTitle .= !empty($release->title) ? sprintf(' - %s', $release->title) : '';

        $params = [
            'title'  => $pullRequestTitle,
            'body'   => $release->github_pr_body,
            'labels' => [
                'release',
            ],
            'assignees' => [
                $username,
            ],
            'base' => $targetBranch,
            'head' => $sourceBranch,
        ];

        if ($existingPullRequestId === 0) {
            $newPullRequest        = $githubPullRequestClient->create($repositoryOwner, $repository, $params);
            $existingPullRequestId = $newPullRequest['id'];

            // Assign the 'release' label to the pull request
            /** @var Issue $githubIssueClient */
            // @phpstan-ignore staticMethod.notFound
            $githubIssueClient = GitHub::issues();
            $githubIssueClient->update($repositoryOwner, $repository, $newPullRequest['number'], [
                'labels'    => array_merge($params['labels'], ['release']),
                'assignees' => [
                    $username,
                ],
            ]);

            $this->info(sprintf('Successfully created GitHub pull request %s', $version));
        } else {
            $githubPullRequestClient->update($repositoryOwner, $repository, $existingPullRequestId, $params);
            $this->info(sprintf('Successfully updated GitHub pull request %s', $version));
        }

        $this->info(sprintf('OK Creating Github pull request for %s', $version));

        return self::SUCCESS;
    }
}
