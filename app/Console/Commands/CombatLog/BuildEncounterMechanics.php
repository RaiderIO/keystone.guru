<?php

namespace App\Console\Commands\CombatLog;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\Dungeon;
use App\Service\Npc\Dtos\NpcEncounterMechanicBuildResult;
use App\Service\Npc\NpcEncounterMechanicServiceInterface;

class BuildEncounterMechanics extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:buildencountermechanics {--dungeon= : Only the encounters of the dungeon with this key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds the boss encounter mechanics that recur across the most recent kills of every observed encounter.';

    public function handle(NpcEncounterMechanicServiceInterface $npcEncounterMechanicService): int
    {
        $dungeonKey = $this->option('dungeon');
        $dungeon    = null;
        if ($dungeonKey !== null) {
            $dungeon = Dungeon::query()->where('key', $dungeonKey)->first();
            if ($dungeon === null) {
                $this->error(sprintf('Dungeon %s not found', $dungeonKey));

                return 1;
            }
        }

        return $this->trackTime(function () use ($npcEncounterMechanicService, $dungeon) {
            $results     = $npcEncounterMechanicService->buildAll($dungeon);
            $dungeonKeys = Dungeon::query()->whereIn('id', $results->pluck('dungeonId')->unique())->pluck('key', 'id');

            foreach ($results as $result) {
                /** @var NpcEncounterMechanicBuildResult $result */
                $encounter = sprintf('%s encounter %d', $dungeonKeys->get($result->dungeonId, (string)$result->dungeonId), $result->encounterId);
                if ($result->rowCountsByType === null) {
                    $this->comment(sprintf('%s: %d kill(s), too few - kept its rows', $encounter, $result->runCount));

                    continue;
                }

                $rowCounts = [];
                foreach ($result->rowCountsByType as $type => $rowCount) {
                    $rowCounts[] = sprintf('%s %d', $type, $rowCount);
                }

                $this->info(sprintf(
                    '%s: %d kill(s), %d row(s)%s',
                    $encounter,
                    $result->runCount,
                    array_sum($result->rowCountsByType),
                    $rowCounts === [] ? '' : sprintf(' (%s)', implode(', ', $rowCounts)),
                ));
            }
        });
    }
}
