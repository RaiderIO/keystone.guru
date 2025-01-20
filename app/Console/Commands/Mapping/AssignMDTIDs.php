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
            Dungeon::RAID_TEMPLE_OF_AHN_QIRAJ,
            Dungeon::RAID_TEMPLE_OF_AHN_QIRAJ_SOD,
        ];

        foreach ($mappingVersions as $mappingVersion) {
            if (empty($dungeonWhitelist) || in_array($mappingVersion->dungeon->key, $dungeonWhitelist)) {
                $index   = 1;
                $enemies = $mappingVersion->enemies->sortBy('npc_id');

                if ($enemies->isEmpty()) {
                    // We don't care for empty mapping versions
                    continue;
                }

                if ($enemies->filter(static fn(Enemy $enemy) => $enemy->mdt_id > 0)->isNotEmpty()) {
                    $this->comment(
                        sprintf(
                            '- Skipping mapping version %d (%s) - already has assigned MDT IDs',
                            $mappingVersion->id,
                            __($mappingVersion->dungeon->name, [], 'en_US')
                        )
                    );

                    continue;
                }

                $this->info(
                    sprintf('Assigning MDT IDs for mapping version %d (%s)',
                        $mappingVersion->id,
                        __($mappingVersion->dungeon->name, [], 'en_US')
                    )
                );

                $previousNpcId = 0;
                foreach ($enemies as $enemy) {

                    if ($previousNpcId !== $enemy->npc_id) {
                        $index         = 1;
                        $previousNpcId = $enemy->npc_id;
                    }

                    $enemy->update(['mdt_id' => $index]);

                    $index++;
                }
            }

        }

        return 0;
    }
}
