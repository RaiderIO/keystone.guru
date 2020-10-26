<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Release;
use Github\Api\Issue;
use Github\Exception\MissingArgumentException;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;

class CreateGithubReleaseTicket extends Command
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
        $this->info(sprintf('>> Creating Github ticket for %s', $version));

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

            /** @var Issue $githubIssueClient */
            $githubIssueClient = GitHub::issues();
            // May throw an exception if it doesn't exist
            $existingIssueId = 0;
            $issueTitle = sprintf('Release %s', $release->version);

            foreach ($githubIssueClient->all($username, $repository, ['filter' => 'all', 'state' => 'open', 'labels' => 'release']) as $githubIssue) {
                if ($githubIssue['title'] === $issueTitle && !isset($githubIssue['pull_request'])) {
                    $existingIssueId = $githubIssue['number'];
                    break;
                }
            }

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
