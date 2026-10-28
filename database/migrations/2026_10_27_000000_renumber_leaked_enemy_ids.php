<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * A test's explicit enemy id pushed the enemies auto increment to these, and the Den of Nalorakk and Murder Row
     * mapping versions after it were seeded with them. The seeder JSON now holds them as 151280-151703.
     */
    private const int LEAKED_ID_MIN = 999999106;

    private const int LEAKED_ID_MAX = 999999529;

    private const int OFFSET = 999847826;

    private const string ENEMY_MODEL_CLASS = 'App\\Models\\Enemy';

    private const array ENEMY_ID_COLUMNS = [
        'kill_zone_enemies'                => 'enemy_id',
        'dungeon_route_enemy_raid_markers' => 'enemy_id',
        'overpulled_enemies'               => 'enemy_id',
        'enemy_infested_votes'             => 'enemy_id',
        'enemies'                          => 'exclusive_enemy_id',
    ];

    private const array POLYMORPHIC_TABLES = [
        'mapping_change_logs',
        'dungeon_route_changes',
        'user_reports',
    ];

    /**
     * Runs before db:seed swaps in the renumbered enemies table, so every reference moves along with it.
     */
    public function up(): void
    {
        $this->shift(self::LEAKED_ID_MIN, self::LEAKED_ID_MAX, -self::OFFSET);

        // InnoDB raises an AUTO_INCREMENT below the current maximum id back to maximum + 1
        DB::statement('ALTER TABLE `enemies` AUTO_INCREMENT = 1');
    }

    public function down(): void
    {
        $this->shift(self::LEAKED_ID_MIN - self::OFFSET, self::LEAKED_ID_MAX - self::OFFSET, self::OFFSET);
    }

    private function shift(int $minId, int $maxId, int $delta): void
    {
        DB::transaction(function () use ($minId, $maxId, $delta): void {
            DB::table('enemies')
                ->whereBetween('id', [$minId, $maxId])
                ->update(['id' => DB::raw(sprintf('`id` + %d', $delta))]);

            foreach (self::ENEMY_ID_COLUMNS as $table => $column) {
                DB::table($table)
                    ->whereBetween($column, [$minId, $maxId])
                    ->update([$column => DB::raw(sprintf('`%s` + %d', $column, $delta))]);
            }

            foreach (self::POLYMORPHIC_TABLES as $table) {
                DB::table($table)
                    ->where('model_class', self::ENEMY_MODEL_CLASS)
                    ->whereBetween('model_id', [$minId, $maxId])
                    ->update(['model_id' => DB::raw(sprintf('`model_id` + %d', $delta))]);
            }
        });
    }
};
