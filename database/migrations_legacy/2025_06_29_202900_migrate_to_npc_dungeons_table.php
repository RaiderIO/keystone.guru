<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('npcs')
            ->where('dungeon_id', '>', 0)
            ->orderBy('id') // ensure chunking is safe and deterministic
            ->chunk(100, function ($npcs) {
                $attributes = [];

                foreach ($npcs as $npc) {
                    $attributes[] = [
                        'npc_id'     => $npc->id,
                        'dungeon_id' => $npc->dungeon_id,
                    ];
                }

                DB::table('npc_dungeons')->insert($attributes);
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
