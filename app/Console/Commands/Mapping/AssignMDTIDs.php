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
            Dungeon::DUNGEON_GATE_OF_THE_SETTING_SUN,
            Dungeon::DUNGEON_MOGU_SHAN_PALACE,
            Dungeon::DUNGEON_SCARLET_HALLS_MOP,
            Dungeon::DUNGEON_SCARLET_MONASTERY_MOP,
            Dungeon::DUNGEON_SCHOLOMANCE_MOP,
            Dungeon::DUNGEON_SHADO_PAN_MONASTERY,
            Dungeon::DUNGEON_SIEGE_OF_NIUZAO_TEMPLE,
            Dungeon::DUNGEON_STORMSTOUT_BREWERY,
            Dungeon::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT,
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
                    /** @var Collection<Enemy> $enemiesByNpcId */
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
