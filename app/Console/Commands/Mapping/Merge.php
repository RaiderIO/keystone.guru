<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Dungeon;
use App\Service\Mapping\MappingService;
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

        $mrList = $githubPrClient->all($username, $repository, [
            'state' => 'open',
            'head'  => $head,
            'base'  => $base
        ]);

        $existingMrId = 0;
        foreach ($mrList as $mr) {
            if ($mr['head']['ref'] === $head && $mr['base']['ref'] === $base) {
                $this->warn('Merge request already exists; not creating a duplicate (which is not possible)');
                $existingMrId = $mr['number'];
                break;
            }
        }


        $changedDungeons = $mappingService->getRecentlyChangedDungeons(true);

        $changedDungeonNames = $changedDungeons->map(function (Dungeon $dungeon)
        {
            return $dungeon->name;
        })->toArray();

        $prTitle = sprintf('Mapping update for %s', implode(', ', $changedDungeonNames));

        if (empty($existingMrId)) {
            try {
                $githubPrClient->create($username, $repository, [
                    'title' => $prTitle,
                    'head'  => $head,
                    'base'  => $base,
                    'body'  => 'This is an automatically generated pull request because changes were detected and committed in https://mapping.keystone.guru/',
                ]);
                $this->info('Merge request created!');
            } catch (ValidationFailedException $ex) {
                $this->warn('Merge request not created - no changes between branches!');
            }
        } else {
            // Title may be changed
            $githubPrClient->update($username, $repository, $existingMrId, [
                'title' => $prTitle
            ]);
            $this->info('Merge request updated!');
        }


        return 0;
    }
}
