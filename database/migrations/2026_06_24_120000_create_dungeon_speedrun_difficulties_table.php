<?php

use App\Models\Dungeon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dungeon_speedrun_difficulties', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dungeon_id');
            $table->unsignedTinyInteger('difficulty');

            $table->index('dungeon_id', 'dsd_dungeon_id_index');
        });

        // Migrate the two boolean columns to child rows
        $rows = DB::table('dungeons')->get();
        foreach ($rows as $row) {
            $difficulties = [];
            if ($row->speedrun_difficulty_10_man_enabled) {
                $difficulties[] = Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_10_MAN];
            }
            if ($row->speedrun_difficulty_25_man_enabled) {
                $difficulties[] = Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN];
            }

            foreach ($difficulties as $difficulty) {
                DB::table('dungeon_speedrun_difficulties')->insert([
                    'dungeon_id' => $row->id,
                    'difficulty' => $difficulty,
                ]);
            }
        }

        Schema::table('dungeons', static function (Blueprint $table): void {
            $table->dropColumn(['speedrun_difficulty_10_man_enabled', 'speedrun_difficulty_25_man_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('dungeons', static function (Blueprint $table): void {
            $table->boolean('speedrun_difficulty_25_man_enabled')->default(0)->after('speedrun_enabled');
            $table->boolean('speedrun_difficulty_10_man_enabled')->default(0)->after('speedrun_enabled');
        });

        $difficultiesByDungeon = DB::table('dungeon_speedrun_difficulties')
            ->get()
            ->groupBy('dungeon_id');

        foreach ($difficultiesByDungeon as $dungeonId => $entries) {
            $difficulties = collect($entries)->pluck('difficulty');

            DB::table('dungeons')
                ->where('id', $dungeonId)
                ->update([
                    'speedrun_difficulty_10_man_enabled' => $difficulties->contains(Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_10_MAN]) ? 1 : 0,
                    'speedrun_difficulty_25_man_enabled' => $difficulties->contains(Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN]) ? 1 : 0,
                ]);
        }

        Schema::drop('dungeon_speedrun_difficulties');
    }
};
