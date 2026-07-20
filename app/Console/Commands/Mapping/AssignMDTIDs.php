<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AssignMDTIDs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:assignmdtids {--dungeon= : Only process this dungeon (by key); omit to process every dungeon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Assigns MDT IDs to enemies that don't have one yet, so they stay identifiable across a mapping version upgrade.";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dungeonKey = $this->option('dungeon');

        $mappingVersionsQuery = MappingVersion::with(['enemies', 'dungeon']);
        if ($dungeonKey !== null) {
            $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();
            $mappingVersionsQuery->where('dungeon_id', $dungeon->id);
        }

        /** @var Collection<int, MappingVersion> $mappingVersions */
        $mappingVersions = $mappingVersionsQuery->get();

        $rows       = [];
        $totalCount = 0;

        foreach ($mappingVersions as $mappingVersion) {
            if ($mappingVersion->dungeon === null) {
                // Dangling mapping_version (no foreign keys in this app) - nothing sensible to report
                continue;
            }

            $enemies = $mappingVersion->enemies()
                ->orderBy('npc_id')
                ->orderBy('id')
                ->get();

            $missingBefore = $enemies->whereNull('mdt_id')->count();
            if ($missingBefore === 0) {
                // Nothing to do for this mapping version
                continue;
            }

            $assigned = 0;
            foreach ($enemies->groupBy('npc_id') as $enemiesByNpcId) {
                /** @var Collection<int, Enemy> $enemiesByNpcId */
                $enemiesByNpcId = $enemiesByNpcId->sortBy('id');
                $maxId          = 0;
                // Determine the max ID first, then assign the max ID to any NPCs that don't have an ID yet
                foreach ($enemiesByNpcId as $enemy) {
                    if ($enemy->mdt_id > 0 && $maxId <= $enemy->mdt_id) {
                        $maxId = $enemy->mdt_id;
                    }
                }

                foreach ($enemiesByNpcId as $enemy) {
                    if (empty($enemy->mdt_id)) {
                        // Increment first, then write
                        $enemy->update(['mdt_id' => ++$maxId]);
                        $assigned++;
                    }
                }
            }

            $totalCount += $assigned;
            $rows[] = [
                __($mappingVersion->dungeon->name),
                $mappingVersion->dungeon_id,
                $mappingVersion->id,
                $mappingVersion->version,
                $missingBefore,
                $missingBefore - $assigned,
            ];
        }

        if (count($rows) > 0) {
            $this->table(
                ['Dungeon', 'Dungeon ID', 'Mapping Version ID', 'Version', 'Missing before', 'Missing after'],
                $rows,
            );
        }

        $this->info(sprintf('Assigned MDT IDs to %d enemies', $totalCount));

        return 0;
    }
}
