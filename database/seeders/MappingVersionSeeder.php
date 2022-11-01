<?php

namespace Database\Seeders;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Artisan;
use Illuminate\Database\Seeder;

/**
 * The Mapping Versions are loaded from mapping_versions.json using the DungeonDataSeeder after this initial seed.
 *
 * @package Database\Seeders
 * @author Wouter
 * @since 30/10/2022
 */
class MappingVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Refresh the mapping versions so that we're sure we get the latest info
        Artisan::call('modelCache:clear', ['--model' => MappingVersion::class]);

        if (MappingVersion::count() !== 0) {
            $this->command->comment('NOT adding Mapping Versions - initial seed has already happened');
            return;
        }

        $this->command->comment(sprintf('Generating new Mapping Versions: %d', MappingVersion::count()));

        // This script creates a new mapping version for each dungeon and assigns the mapping version to all existing
        // elements that need a mapping version to continue
        foreach (Dungeon::all() as $dungeon) {
            /** @var $dungeon Dungeon */
            $mappingVersion = MappingVersion::create([
                'dungeon_id' => $dungeon->id,
                'version'    => 1,
            ]);
            $this->command->comment(sprintf('- Created new mapping version for %s', __($dungeon->name)));

            $updatedDungeonFloorSwitchMarkers = $dungeon->dungeonfloorswitchmarkers()->update([
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->command->comment(sprintf('-- Updated %d dungeon floor switch markers', $updatedDungeonFloorSwitchMarkers));

            $updatedEnemies = $dungeon->enemies()->update([
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->command->comment(sprintf('-- Updated %d enemies', $updatedEnemies));

            $updatedEnemyPacks = $dungeon->enemypacks()->update([
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->command->comment(sprintf('-- Updated %d enemy packs', $updatedEnemyPacks));

            $updatedEnemyPatrols = $dungeon->enemypatrols()->update([
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->command->comment(sprintf('-- Updated %d enemy patrols', $updatedEnemyPatrols));

            // Only the map icons that are related to a mapping
            $updatedMapIcons = $dungeon->mapicons()->where('dungeon_route_id', -1)->update([
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->command->comment(sprintf('-- Updated %d map icons', $updatedMapIcons));

            $updatedMountableAreas = $dungeon->mountableareas()->update([
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->command->comment(sprintf('-- Updated %d mountable areas', $updatedMountableAreas));
        }
    }
}
