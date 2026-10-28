<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Mirrors the main schema's renumber_leaked_enemy_ids migration for the one combat log table holding enemy ids.
     */
    private const int LEAKED_ID_MIN = 999999106;

    private const int LEAKED_ID_MAX = 999999529;

    private const int OFFSET = 999847826;

    public function up(): void
    {
        $this->shift(self::LEAKED_ID_MIN, self::LEAKED_ID_MAX, -self::OFFSET);
    }

    public function down(): void
    {
        $this->shift(self::LEAKED_ID_MIN - self::OFFSET, self::LEAKED_ID_MAX - self::OFFSET, self::OFFSET);
    }

    private function shift(int $minId, int $maxId, int $delta): void
    {
        DB::table('combat_log_route_enemy_resolutions')
            ->whereBetween('enemy_id', [$minId, $maxId])
            ->update(['enemy_id' => DB::raw(sprintf('`enemy_id` + %d', $delta))]);
    }
};
