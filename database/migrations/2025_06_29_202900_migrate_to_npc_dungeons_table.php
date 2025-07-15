<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Npc::where('dungeon_id', '>', 0)->chunk(100, function (Collection $npcs) {
            $attributes = [];
            foreach ($npcs as $npc) {
                $attributes[] = [
                    'npc_id'     => $npc->id,
                    'dungeon_id' => $npc->dungeon_id,
                ];
            }

            NpcDungeon::insert($attributes);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('npc_dungeons')->truncate();
    }
};
