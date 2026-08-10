<?php

namespace App\Console\Commands\Mapping;

use App\Models\DungeonKey;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\RaidKey;
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
        /** @var Collection<int, MappingVersion> $mappingVersions */
        $mappingVersions = MappingVersion::with([
            'enemyPacks',
            'dungeon',
        ])->get();

        $dungeonWhitelist = [
            RaidKey::THE_EYE->value,
            RaidKey::SERPENTSHRINE_CAVERN->value,
            //            DungeonKey::GATE_OF_THE_SETTING_SUN->value,
            //            DungeonKey::MOGU_SHAN_PALACE->value,
            //            DungeonKey::SCARLET_HALLS_MOP->value,
            //            DungeonKey::SCARLET_MONASTERY_MOP->value,
            //            DungeonKey::SCHOLOMANCE_MOP->value,
            //            DungeonKey::SHADO_PAN_MONASTERY->value,
            //            DungeonKey::SIEGE_OF_NIUZAO_TEMPLE->value,
            //            DungeonKey::STORMSTOUT_BREWERY->value,
            //            DungeonKey::TEMPLE_OF_THE_JADE_SERPENT->value,
        ];

        $count = 0;
        foreach ($mappingVersions as $mappingVersion) {
            if (empty($dungeonWhitelist) || in_array($mappingVersion->dungeon->key, $dungeonWhitelist)) { // @phpstan-ignore empty.variable
                /** @var Collection<int, EnemyPack> $enemyPacks */
                $enemyPacks = $mappingVersion->enemyPacks()
                    ->orderBy('id')
                    ->get();

                $index = 0;
                foreach ($enemyPacks as $enemyPack) {
                    // Increment first, then write
                    $enemyPack->update(['group' => ++$index]);
                    $count++;
                }
            }
        }

        $this->info(sprintf('Assigned groups to %d packs', $count));

        return 0;
    }
}
