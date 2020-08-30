<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Github\Api\PullRequest;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Console\Command;

class Merge extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:merge {branch=master}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a merge request for the current branch to master any saved mapping to Git';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = config('keystoneguru.github_username');
        $repository = config('keystoneguru.github_repository');
        $head = 'mapping';
        $base = 'master';

        /** @var PullRequest $githubPrClient */
        $githubPrClient = GitHub::pr();

        $mrList = $githubPrClient->all($username, $repository, [
            'state' => 'open',
            'head'  => 'mapping',
            'base'  => 'master'
        ]);

        $mrAlreadyExists = false;
        foreach ($mrList as $mr) {
            if ($mr['head']['ref'] === $head && $mr['base']['ref'] === $base) {
                $this->warn('Merge request already exists; not creating a duplicate (which is not possible)');
                $mrAlreadyExists = true;
                break;
            }
        }

        if (!$mrAlreadyExists) {
            $githubPrClient->create($username, $repository, [
                'title' => 'Mapping update',
                'head'  => $head,
                'base'  => $base,
                'body'  => 'This is an automatically generated pull request because changes were detected and committed in https://mapping.keystone.guru/',
            ]);
            $this->info('Merge request created!');
        }


        return 0;
    }
}
