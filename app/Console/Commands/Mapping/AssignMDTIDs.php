<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Expansion;
use App\Models\Mapping\MappingCommitLog;
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
    protected $description = 'Assigns MDT IDs to a mapping that doesn\'t have them yet.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var Collection|MappingVersion[] $mappingVersions */
        $mappingVersions = MappingVersion::with(['enemies', 'dungeon'])->get();


        foreach ($mappingVersions as $mappingVersion) {
            if ($mappingVersion->dungeon->expansion->shortname === Expansion::EXPANSION_CLASSIC) {
                $this->info(sprintf('Assigning MDT IDs for dungeon %s', __($mappingVersion->dungeon->name, [], 'en')));
                $index   = 1;
                $enemies = $mappingVersion->enemies->sortBy('npc_id');

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
