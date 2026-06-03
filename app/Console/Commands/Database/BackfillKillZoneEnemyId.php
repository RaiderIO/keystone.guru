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
    protected $signature = 'ksg:backfill-kill-zone-enemy-id {--chunk=50000 : Rows per batch}';

    protected $description = 'Backfills enemy_id on kill_zone_enemies for existing records (one-time operation)';

    public function handle(): int
    {
        $chunkSize = (int)$this->option('chunk');

        $minId = (int)DB::table('kill_zone_enemies')->whereNull('enemy_id')->min('id');
        $maxId = (int)DB::table('kill_zone_enemies')->whereNull('enemy_id')->max('id');

        if ($minId === 0) {
            $this->info('All kill_zone_enemies already have enemy_id set. Nothing to do.');

            return 0;
        }

        $total  = (int)DB::table('kill_zone_enemies')->whereNull('enemy_id')->count();
        $chunks = (int)ceil(($maxId - $minId + 1) / $chunkSize);

        $this->info(sprintf('Backfilling %s rows across %s chunks (chunk size: %s)...', number_format($total), $chunks, number_format($chunkSize)));

        $bar      = $this->output->createProgressBar($chunks);
        $updated  = 0;
        $orphaned = 0;

        for($start = $maxId; $start >= $minId; $start -= $chunkSize) {
            $end = $start - $chunkSize + 1;

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

            $stillNull = (int)DB::table('kill_zone_enemies')
                ->whereNull('enemy_id')
                ->whereBetween('id', [$end, $start])
                ->count();

            $orphaned += $stillNull;
            $updated += ($chunkSize - $stillNull);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('Updated: %s rows', number_format($updated)));

        if ($orphaned > 0) {
            $this->warn(sprintf('%s orphan rows (no matching enemy) were left with enemy_id = NULL.', number_format($orphaned)));
            $this->warn('These belong to dungeon routes whose enemies were deleted or are from outdated mapping versions.');
        }

        return 0;
    }
}
