<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RepairBrokenThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:repairbrokenthumbnails {--dry-run : Report what would happen without deleting or queueing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repairs broken dungeon route thumbnails: deletes rows that are not backed by a usable File (file_id null or the File row is gone), and re-queues routes whose thumbnail File exists but its disk object is missing.';

    public function handle(ThumbnailServiceInterface $thumbnailService): int
    {
        $dryRun = (bool)$this->option('dry-run');

        $this->deleteFilelessThumbnails($dryRun);
        $this->requeueThumbnailsMissingFromDisk($thumbnailService, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Deletes thumbnail rows that are not backed by a usable File - either file_id is null, or it
     * points at a File row that no longer exists. Such rows leave has_thumbnail inconsistent; once
     * they are gone the route's cards correctly fall back to the dungeon image.
     */
    private function deleteFilelessThumbnails(bool $dryRun): void
    {
        // whereDoesntHave('file') covers both a null file_id and a file_id pointing at a missing row.
        $query = DungeonRouteThumbnail::query()->whereDoesntHave('file');

        $total = $query->clone()->count();

        $this->info(sprintf('Found %d fileless thumbnail row(s).', $total));

        if ($total === 0) {
            return;
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

        $this->info(sprintf(
            '%s %d fileless thumbnail row(s) across %d route(s).',
            $dryRun ? 'Would delete' : 'Deleted',
            $deleted,
            count($affectedDungeonRouteIds),
        ));
    }

    /**
     * Detects thumbnails whose File row still exists but whose object is missing from disk (these
     * render a thumbnail URL that 403s), and re-queues each affected route for regeneration.
     *
     * @note Regeneration is performed by the queued thumbnail jobs, so this only takes effect once
     *       thumbnail generation itself is operational.
     */
    private function requeueThumbnailsMissingFromDisk(ThumbnailServiceInterface $thumbnailService, bool $dryRun): void
    {
        $affectedDungeonRouteIds = [];

        DungeonRouteThumbnail::query()
            ->whereHas('file')
            ->with('file')
            ->chunkById(100, function ($thumbnails) use (&$affectedDungeonRouteIds): void {
                foreach ($thumbnails as $thumbnail) {
                    /** @var DungeonRouteThumbnail $thumbnail */
                    $file = $thumbnail->file;

                    if ($file !== null && !Storage::disk($file->disk)->exists($file->path)) {
                        $affectedDungeonRouteIds[$thumbnail->dungeon_route_id] = true;
                    }
                }
            });

        $this->info(sprintf('Found %d route(s) with a thumbnail File whose disk object is missing.', count($affectedDungeonRouteIds)));

        if ($affectedDungeonRouteIds === []) {
            return;
        }

        $requeued = 0;

        DungeonRoute::query()
            ->whereIn('id', array_keys($affectedDungeonRouteIds))
            ->with(['dungeon', 'mappingVersion'])
            ->chunkById(100, function ($dungeonRoutes) use ($thumbnailService, &$requeued, $dryRun): void {
                foreach ($dungeonRoutes as $dungeonRoute) {
                    /** @var DungeonRoute $dungeonRoute */
                    if (!$dryRun) {
                        $thumbnailService->queueThumbnailRefresh($dungeonRoute, true);
                    }

                    $requeued++;
                }
            });

        $this->info(sprintf(
            '%s %d route(s) for thumbnail regeneration.',
            $dryRun ? 'Would re-queue' : 'Re-queued',
            $requeued,
        ));
    }
}
