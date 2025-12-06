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
        $result = 0;

        $version = $this->argument('version');
        $release = $releaseRepository->findReleaseByVersion($version);

        $this->info(sprintf('>> Creating Github pull request for %s', $version));

        if ($release !== null) {
            $sourceBranch = 'development';
            $targetBranch = 'master';

            $username        = config('keystoneguru.github_username');
            $repositoryOwner = config('keystoneguru.github_repository_owner');
            $repository      = config('keystoneguru.github_repository');

            /** @var PullRequest $githubPullRequestClient */
            $githubPullRequestClient = GitHub::pr();
            // May throw an exception if it doesn't exist
            $existingPullRequestId = 0;
            $pullRequestTitle      = sprintf('Release %s', $release->version);

            // Only gets the first page - but good enough
            foreach ($githubPullRequestClient->all($repositoryOwner, $repository, [
                'state'  => 'open',
                'labels' => 'release',
            ]) as $githubPullRequest) {
                if (str_starts_with((string)$githubPullRequest['head']['repo']['full_name'], sprintf('%s/%s', $username, $repository)) &&
                    $githubPullRequest['head']['ref'] === $sourceBranch &&
                    str_starts_with((string)$githubPullRequest['base']['repo']['full_name'], sprintf('%s/%s', $username, $repository)) &&
                    $githubPullRequest['base']['ref'] === $targetBranch) {
                    $existingPullRequestId = $githubPullRequest['number'];
                    break;
                }
            }

            // Append the release title here so that we don't match on it earlier
            $pullRequestTitle .= !empty($release->title) ? sprintf(' - %s', $release->title) : '';

            $params = [
                'title'  => $pullRequestTitle,
                'body'   => $release->github_body,
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
                $githubIssueClient = GitHub::issues();
                $githubIssueClient->update($repositoryOwner, $repository, $newPullRequest['number'], [
                    'labels'    => array_merge($params['labels'], ['release']),
                    'assignees' => [
                        $username,
                    ],
                ]);

                $this->info(sprintf('Successfully created GitHub pull request %s', $version));
                $result = 1;
            } else {
                $githubPullRequestClient->update($repositoryOwner, $repository, $existingPullRequestId, $params);
                $this->info(sprintf('Successfully updated GitHub pull request %s', $version));
                $result = 2;
            }

            // Add labels to the pull request
            // The library I'm using cannot do this yet - do it later?
            //            if ($existingPullRequestId !== 0) {
            //
            //                /** @var Issue $githubIssueClient */
            //                $githubIssueClient = GitHub::issues();
            //
            //                $githubIssueClient->
            //            }
        } else {
            $this->error(sprintf('Unable to find release %s', $version));
        }

        $this->info(sprintf('OK Creating Github pull request for %s', $version));

        return $result;
    }
}
