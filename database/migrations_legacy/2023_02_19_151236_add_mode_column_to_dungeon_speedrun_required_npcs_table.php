<?php

use App\Models\Dungeon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->integer('mode')->after('npc5_id');

            $table->dropIndex(['floor_id']);
            $table->index(['floor_id', 'mode']);
        });

        DB::update(
            '
            UPDATE `dungeon_speedrun_required_npcs` SET mode = :mode
            ',
            ['mode' => Dungeon::DIFFICULTY_25_MAN],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->dropColumn('mode');

            $table->dropIndex(['floor_id', 'mode']);
            $table->index(['floor_id']);
        });
    }
};
