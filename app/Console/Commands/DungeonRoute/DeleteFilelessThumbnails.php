<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use Illuminate\Console\Command;

class DeleteFilelessThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:deletefilelessthumbnails {--dry-run : List what would be deleted without deleting anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes dungeon route thumbnail rows that are not backed by a usable File (file_id null or the File row is gone), so that has_thumbnail reflects reality and cards fall back to the dungeon image.';

    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');

        // A thumbnail is "fileless" when its file_id is null, or when file_id points at a File
        // row that no longer exists. whereDoesntHave('file') covers both cases in one query.
        $query = DungeonRouteThumbnail::query()->whereDoesntHave('file');

        $total = $query->clone()->count();

        $this->info(sprintf('Found %d fileless thumbnail row(s).', $total));

        if ($total === 0) {
            return self::SUCCESS;
        }

        $affectedDungeonRouteIds = [];
        $deleted                 = 0;

        // Delete per-model (not a bulk delete) so the DungeonRouteThumbnail deleting observer runs
        // and cleans up any lingering File/disk object; chunk to keep memory bounded on large sets.
        $query->clone()->chunkById(100, function ($thumbnails) use (&$affectedDungeonRouteIds, &$deleted, $dryRun): void {
            foreach ($thumbnails as $thumbnail) {
                /** @var DungeonRouteThumbnail $thumbnail */
                $affectedDungeonRouteIds[$thumbnail->dungeon_route_id] = true;

                if (!$dryRun) {
                    $thumbnail->delete();
                }

                $deleted++;
            }
        });

        // Report how many routes end up with no thumbnails at all (has_thumbnail becomes false for
        // them, so their cards fall back to the dungeon image instead of a broken thumbnail URL).
        $routesLosingAllThumbnails = DungeonRoute::query()
            ->whereIn('id', array_keys($affectedDungeonRouteIds))
            ->whereDoesntHave('dungeonRouteThumbnails', function ($query): void {
                $query->whereHas('file');
            })
            ->count();

        if ($dryRun) {
            $this->info(sprintf(
                'Dry run: would delete %d thumbnail row(s) across %d route(s); %d route(s) would be left without any file-backed thumbnail.',
                $deleted,
                count($affectedDungeonRouteIds),
                $routesLosingAllThumbnails,
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Deleted %d thumbnail row(s) across %d route(s); %d route(s) are now without any file-backed thumbnail and will fall back to the dungeon image (they will regenerate through the normal refresh flow).',
            $deleted,
            count($affectedDungeonRouteIds),
            $routesLosingAllThumbnails,
        ));

        return self::SUCCESS;
    }
}
