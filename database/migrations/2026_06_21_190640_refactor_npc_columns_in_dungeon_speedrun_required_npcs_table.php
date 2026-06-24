<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dungeon_speedrun_required_npc_npcs', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dungeon_speedrun_required_npc_id');
            $table->unsignedInteger('npc_id');

            $table->index('dungeon_speedrun_required_npc_id', 'dsrnn_dungeon_speedrun_required_npc_id_index');
        });

        // Migrate existing npc_id…npc5_id columns to child rows
        $rows = DB::table('dungeon_speedrun_required_npcs')->get();
        foreach ($rows as $row) {
            $npcIds = array_filter([
                $row->npc_id,
                $row->npc2_id,
                $row->npc3_id,
                $row->npc4_id,
                $row->npc5_id,
            ]);

            foreach ($npcIds as $npcId) {
                DB::table('dungeon_speedrun_required_npc_npcs')->insert([
                    'dungeon_speedrun_required_npc_id' => $row->id,
                    'npc_id'                           => $npcId,
                ]);
            }
        }

        Schema::table('dungeon_speedrun_required_npcs', static function (Blueprint $table): void {
            $table->dropColumn(['npc_id', 'npc2_id', 'npc3_id', 'npc4_id', 'npc5_id']);
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_speedrun_required_npcs', static function (Blueprint $table): void {
            $table->unsignedInteger('npc_id')->after('floor_id');
            $table->unsignedInteger('npc2_id')->nullable()->after('npc_id');
            $table->unsignedInteger('npc3_id')->nullable()->after('npc2_id');
            $table->unsignedInteger('npc4_id')->nullable()->after('npc3_id');
            $table->unsignedInteger('npc5_id')->nullable()->after('npc4_id');
        });

        // Restore up to 5 npc_ids per parent row (first child → npc_id, rest → npc2..5)
        $children = DB::table('dungeon_speedrun_required_npc_npcs')
            ->orderBy('dungeon_speedrun_required_npc_id')
            ->orderBy('id')
            ->get()
            ->groupBy('dungeon_speedrun_required_npc_id');

        foreach ($children as $parentId => $entries) {
            $values                = $entries->pluck('npc_id')->values();
            $updateData            = ['npc_id' => $values->get(0)];
            $updateData['npc2_id'] = $values->get(1);
            $updateData['npc3_id'] = $values->get(2);
            $updateData['npc4_id'] = $values->get(3);
            $updateData['npc5_id'] = $values->get(4);

            DB::table('dungeon_speedrun_required_npcs')
                ->where('id', $parentId)
                ->update($updateData);
        }

        Schema::drop('dungeon_speedrun_required_npc_npcs');
    }
};
