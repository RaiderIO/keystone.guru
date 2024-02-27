<?php

use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\Npc\NpcEnemyForces;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Don't do anything - this should already be done and the result committed to repository
        if (config('app.env') !== 'mapping') {
            return;
        }

        $allNpcs = Npc::all();
        foreach (MappingVersion::all() as $mappingVersion) {
            /** @var MappingVersion $mappingVersion */
            foreach ($allNpcs->whereIn('dungeon_id', [-1, $mappingVersion->dungeon_id]) as $npc) {
                /** @var Npc $npc */
                NpcEnemyForces::create([
                    'npc_id' => $npc->id,
                    'mapping_version_id' => $mappingVersion->id,
                    'enemy_forces' => $npc->enemy_forces > 0 ? $npc->enemy_forces : 0,
                    'enemy_forces_teeming' => $npc->enemy_forces_teeming > 0 ? $npc->enemy_forces_teeming : null,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't do anything - this should already be done and the result committed to repository
        if (config('app.env') !== 'mapping') {
            return;
        }

        DB::table('npc_enemy_forces')->truncate();
    }
};
