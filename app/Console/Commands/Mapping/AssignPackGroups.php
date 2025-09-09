<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AssignPackGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:assignpackgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Assigns groups to packs of enemies in a mapping that doesn't have them yet.";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var Collection<MappingVersion> $mappingVersions */
        $mappingVersions = MappingVersion::with([
            'enemyPacks',
            'dungeon',
        ])->get();

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
                /** @var Collection<EnemyPack> $enemyPacks */
                $enemyPacks = $mappingVersion->enemyPacks()
                    ->orderBy('id')
                    ->get();

                $index = 0;
                foreach ($enemyPacks as $enemyPack) {
                    // Increment first, then write
                    $enemyPack->update(['group' => ++$index]);
                }
            }

        }

        return 0;
    }
}
