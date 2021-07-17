<?php

namespace App\Console\Commands\Github;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Github\Api\Issue;
use Github\Exception\MissingArgumentException;
use GrahamCampbell\GitHub\Facades\GitHub;

class CreateGithubReleaseTicket extends GithubReleaseCommand
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:githubreleaseticket {version?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new ticket for a release on the Keystone.guru Github';

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
     * @return int
     * @throws MissingArgumentException
     */
    public function handle()
    {
        $result = 0;

        $version = $this->argument('version');
        $release = $this->findReleaseByVersion($version);

        $this->info(sprintf('>> Creating Github ticket for %s', $version));

        if ($release !== null) {
            $username = config('keystoneguru.github_username');
            $repository = config('keystoneguru.github_repository');

            /** @var Issue $githubIssueClient */
            $githubIssueClient = GitHub::issues();
            // May throw an exception if it doesn't exist
            $existingIssueId = 0;
            $issueTitle = sprintf('Release %s', $release->version);

            // Only gets the first page - but good enough
            foreach ($githubIssueClient->all($username, $repository, ['filter' => 'all', 'state' => 'all', 'labels' => 'release']) as $githubIssue) {
                if (strpos($githubIssue['title'], $issueTitle) === 0 && !isset($githubIssue['pull_request'])) {
                    $existingIssueId = $githubIssue['number'];
                    break;
                }
            }

            // Append the release title here so that we don't match on it earlier
            $issueTitle .= !empty($release->title) ? sprintf(' - %s', $release->title) : '';

            $params = [
                'title'     => $issueTitle,
                'body'      => $release->github_body,
                'labels'    => [
                    'release'
                ],
                'assignees' => [
                    $username
                ]
            ];

            if ($existingIssueId === 0) {
                $githubIssueClient->create($username, $repository, $params);
                $this->info(sprintf('Successfully created GitHub issue %s', $version));
                $result = 1;
            } else {
                $githubIssueClient->update($username, $repository, $existingIssueId, $params);
                $this->info(sprintf('Successfully updated GitHub issue %s', $version));
                $result = 2;
            }


        } else {
            $this->error(sprintf('Unable to find release %s', $version));
        }

        $this->info(sprintf('OK Creating Github issue for %s', $version));

        return $result;
    }
}
