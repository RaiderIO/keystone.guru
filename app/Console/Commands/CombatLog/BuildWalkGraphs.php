<?php

namespace App\Console\Commands\CombatLog;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\WalkGraph\WalkGraphServiceInterface;

class BuildWalkGraphs extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:buildwalkgraphs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds the walk graph of every ui map from the most recent runs, and the walking distances between enemy packs.';

    public function handle(WalkGraphServiceInterface $walkGraphService): int
    {
        return $this->trackTime(function () use ($walkGraphService) {
            $result = $walkGraphService->buildAll();

            $this->info(sprintf('Built %d walk graph(s), wrote %d pack distance(s)', $result['graphs'], $result['distances']));
        });
    }
}
