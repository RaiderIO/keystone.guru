<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Mapping\MappingCommitLog;
use App\Service\Mapping\MappingService;
use Carbon\Carbon;
use Github\Api\PullRequest;
use Github\Exception\MissingArgumentException;
use Github\Exception\ValidationFailedException;
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
    protected $signature = 'mapping:merge {branch=development}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a merge request for the current branch to master any saved mapping to Git';

    /**
     * Execute the console command.
     *
     * @param MappingService $mappingService
     * @return int
     * @throws MissingArgumentException
     */
    public function handle(MappingService $mappingService)
    {
        $username = config('keystoneguru.github_username');
        $repository = config('keystoneguru.github_repository');
        $head = 'mapping';
        $base = 'development';

        /** @var PullRequest $githubPrClient */
        $githubPrClient = GitHub::pr();

        $prList = $githubPrClient->all($username, $repository, [
            'state' => 'open',
            'head'  => $head,
            'base'  => $base
        ]);

        $existingPrId = 0;
        foreach ($prList as $pr) {
            if ($pr['head']['ref'] === $head && $pr['base']['ref'] === $base) {
                $this->warn('Pull request already exists; not creating a duplicate (which is not possible)');
                $existingPrId = $pr['number'];
                break;
            }
        }

        // Build the title for the pull request
        $changedDungeonNames = $mappingService->getRecentlyChangedDungeons()->pluck(['name'])->map(function(string $name){
            return __($name);
        });
        if ($changedDungeonNames->count() > 4) {
            $prTitle = sprintf('Mapping update for %s dungeons', $changedDungeonNames->count());
        } else if ($changedDungeonNames->isEmpty()) {
            $prTitle = 'Mapping update for no dungeons';
        } else {
            $prTitle = sprintf('Mapping update for %s', $changedDungeonNames->implode(', '));
        }

        if (empty($existingPrId)) {
            try {
                $githubPrClient->create($username, $repository, [
                    'title'  => $prTitle,
                    'head'   => $head,
                    'base'   => $base,
                    'body'   => 'This is an automatically generated pull request because changes were detected and committed in https://mapping.keystone.guru/',
                    'labels' => [
                        'mapping'
                    ],
                ]);
                $this->info('Pull request created!');

                // If we're creating a new merge request, everything before this has been merged - except the most recent commit
                // (since that's what we're making this MR for in the first place) which we'll filter out
                MappingCommitLog::whereDate('created_at', '<', Carbon::now()->subMinutes(3))->update(['merged' => 1]);
            } catch (ValidationFailedException $ex) {
                $this->warn('Pull request not created - no changes between branches!');
            }
        } else {
            // Title may be changed
            $githubPrClient->update($username, $repository, $existingPrId, [
                'title' => $prTitle
            ]);
            $this->info('Pull request updated!');
        }


        return 0;
    }
}
