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
    protected $signature = 'mapping:assignmdtids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Assigns MDT IDs to a mapping that doesn't have them yet.";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var Collection<MappingVersion> $mappingVersions */
        $mappingVersions = MappingVersion::with(['enemies', 'dungeon'])->get();

        $dungeonWhitelist = [
            Dungeon::RAID_SCARLET_ENCLAVE,
        ];

        foreach ($mappingVersions as $mappingVersion) {
            if (empty($dungeonWhitelist) || in_array($mappingVersion->dungeon->key, $dungeonWhitelist)) {
                $enemies = $mappingVersion->enemies()
                    ->orderBy('npc_id')
                    ->orderBy('id')
                    ->get();

                if ($enemies->isEmpty()) {
                    // We don't care for empty mapping versions
                    continue;
                }

                foreach ($enemies->groupBy('npc_id') as $npcId => $enemiesByNpcId) {
                    $enemiesByNpcId = $enemiesByNpcId->sortBy('id');
                    $maxId = 0;
                    // Determine the max ID first, then assign the max ID to any NPCs that don't have an ID yet
                    foreach ($enemiesByNpcId as $enemy) {
                        if ($enemy->mdt_id > 0 && $maxId <= $enemy->mdt_id) {
                            $maxId = $enemy->mdt_id;
                        }
                    }

                    foreach($enemiesByNpcId as $enemy) {
                        if(empty($enemy->mdt_id)){
                            // Increment first, then write
                            $enemy->update(['mdt_id' => ++$maxId]);
                        }
                    }
                }
            }

        }

        return 0;
    }
}
