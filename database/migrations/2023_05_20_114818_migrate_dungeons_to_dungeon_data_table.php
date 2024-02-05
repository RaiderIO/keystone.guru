<?php

use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Migrations\Migration;

class MigrateDungeonsToDungeonDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Don't do anything - this should already be done and the result committed to repository
        if (config('app.env') !== 'mapping') {
            return;
        }

        foreach (MappingVersion::all() as $mappingVersion) {
            /** @var $mappingVersion MappingVersion */

            $mappingVersion->update([
                'enemy_forces_required'           =>
                    empty($mappingVersion->dungeon->enemy_forces_required) ? 0 : $mappingVersion->dungeon->enemy_forces_required,
                'enemy_forces_required_teeming'   =>
                    empty($mappingVersion->dungeon->enemy_forces_required_teeming) ? null : $mappingVersion->dungeon->enemy_forces_required_teeming,
                'enemy_forces_shrouded'           =>
                    empty($mappingVersion->dungeon->enemy_forces_shrouded) ? null : $mappingVersion->dungeon->enemy_forces_shrouded,
                'enemy_forces_shrouded_zul_gamux' =>
                    empty($mappingVersion->dungeon->enemy_forces_shrouded_zul_gamux) ? null : $mappingVersion->dungeon->enemy_forces_shrouded_zul_gamux,
                'timer_max_seconds'               => $mappingVersion->dungeon->timer_max_seconds,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Don't do anything - this should already be done and the result committed to repository
        if (config('app.env') !== 'mapping') {
            return;
        }

        foreach (MappingVersion::all() as $mappingVersion) {
            /** @var $mappingVersion MappingVersion */

            $mappingVersion->update([
                'enemy_forces_required'           => 0,
                'enemy_forces_required_teeming'   => null,
                'enemy_forces_shrouded'           => null,
                'enemy_forces_shrouded_zul_gamux' => null,
                'timer_max_seconds'               => 0,
            ]);
        }
    }
}
