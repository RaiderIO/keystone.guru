<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfills the enemy_id column on kill_zone_enemies for existing records.
 *
 * The enemy_id migration adds the nullable column but does not populate existing
 * rows because of lock-wait timeouts on large tables. Run this command once after
 * deploying the migration to resolve all historical pull enemies to their enemy IDs.
 *
 * Usage: php artisan ksg:backfill-kill-zone-enemy-id [--chunk=50000]
 */
class BackfillKillZoneEnemyId extends Command
{
    protected $signature = 'ksg:backfill-kill-zone-enemy-id {--chunk=50000 : Rows per batch} {--min= : Lower bound ID (inclusive)} {--max= : Upper bound ID (inclusive)}';

    protected $description = 'Backfills enemy_id on kill_zone_enemies for existing records (one-time operation)';

    public function handle(): int
    {
        $chunkSize = (int)$this->option('chunk');

        $minOption = $this->option('min');
        $maxOption = $this->option('max');

        $query = DB::table('kill_zone_enemies')->whereNull('enemy_id');

        if ($minOption !== null && $maxOption !== null) {
            $minId = (int)$minOption;
            $maxId = (int)$maxOption;
        } else {
            $minId = (int)$query->min('id');
            $maxId = (int)$query->max('id');
        }

        if ($minId === 0) {
            $this->info('All kill_zone_enemies already have enemy_id set. Nothing to do.');

            return 0;
        }

        $updated  = 0;
        $orphaned = 0;

        for ($start = $maxId; $start >= $minId; $start -= $chunkSize) {
            $end = max($start - $chunkSize + 1, $minId);

            DB::statement("
                UPDATE kill_zone_enemies kze
                JOIN kill_zones kz ON kz.id = kze.kill_zone_id
                JOIN dungeon_routes dr ON dr.id = kz.dungeon_route_id
                JOIN enemies e
                    ON kze.npc_id = COALESCE(e.mdt_npc_id, e.npc_id)
                    AND kze.mdt_id = e.mdt_id
                    AND e.mapping_version_id = dr.mapping_version_id
                SET kze.enemy_id = e.id
                WHERE kze.id BETWEEN ? AND ?
                  AND kze.enemy_id IS NULL
            ", [$end, $start]);

            $stillNull = DB::table('kill_zone_enemies')
                ->whereNull('enemy_id')
                ->whereBetween('id', [$end, $start])
                ->count();

            if ($stillNull > 0) {
                DB::table('kill_zone_enemies')
                    ->whereNull('enemy_id')
                    ->whereBetween('id', [$end, $start])
                    ->delete();
            }

            $chunkRowCount = $start - $end + 1;
            $orphaned += $stillNull;
            $updated += ($chunkRowCount - $stillNull);
        }

        $this->info(sprintf('Updated: %s rows', number_format($updated)));

        if ($orphaned > 0) {
            $this->warn(sprintf('%s orphan rows (no matching enemy) were deleted.', number_format($orphaned)));
        }

        return 0;
    }
}
