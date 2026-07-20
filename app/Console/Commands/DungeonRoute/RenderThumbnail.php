<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RenderThumbnail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:renderthumbnail {publicKey : The public key of the dungeon route} {--floor= : A specific floor index to render (defaults to all facade floors)} {--disk=local : The filesystem disk to write to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dev-only (Path A): renders a route thumbnail from THIS checkout\'s code to a file WITHOUT touching the shared database, so you can inspect how the current code renders. Must run in a Chrome-capable container - see the generating-thumbnails skill.';

    public function handle(ThumbnailServiceInterface $thumbnailService): int
    {
        if (app()->isProduction()) {
            $this->error('This command must not be run in production.');

            return self::FAILURE;
        }

        /** @var DungeonRoute|null $dungeonRoute */
        $dungeonRoute = DungeonRoute::where('public_key', $this->argument('publicKey'))->first();

        if ($dungeonRoute === null) {
            $this->error(sprintf('No dungeon route found with public key %s.', $this->argument('publicKey')));

            return self::FAILURE;
        }

        // Force the target disk so the render lands on the ISOLATED local disk (storage/app/private)
        // instead of the shared, bind-mounted public disk.
        $disk = (string)$this->option('disk');

        // A remote (S3) disk defeats the whole point of this command - ThumbnailService silently
        // redirects a real-S3 write to the public disk instead, which is exactly the shared disk
        // this command exists to avoid touching.
        if (config(sprintf('filesystems.disks.%s.driver', $disk)) === 's3') {
            $this->error(sprintf('--disk=%s is a remote disk; this command only supports isolated local disks.', $disk));

            return self::FAILURE;
        }

        config(['filesystems.default' => $disk]);

        // The database is shared across the main stack and all worktrees, so persisting a thumbnail
        // here would replace the real (public-disk) row with one pointing at this worktree's private
        // disk and break the route everywhere else. Render inside a transaction we always roll back:
        // the resized JPG is written to disk (Storage writes are not transactional and survive the
        // rollback), while the DungeonRouteThumbnail/File rows and the route timestamp are discarded.
        $renderedPaths = [];

        DB::beginTransaction();

        try {
            foreach ($this->resolveFloors($dungeonRoute) as $floor) {
                $this->info(sprintf('Rendering %s floor %d to the %s disk...', $dungeonRoute->public_key, $floor->index, $disk));

                $thumbnail = $thumbnailService->createThumbnail($dungeonRoute, $floor->index, 0);

                if ($thumbnail?->file === null) {
                    $this->error(sprintf('Failed to render floor %d (check that this container has Chrome - see the generating-thumbnails skill).', $floor->index));

                    return self::FAILURE;
                }

                $renderedPaths[] = $thumbnail->file->path;
            }
        } finally {
            DB::rollBack();
        }

        $storageSubdir = $disk === 'local' ? 'private' : $disk;
        foreach ($renderedPaths as $renderedPath) {
            $this->info(sprintf('  -> storage/app/%s/%s', $storageSubdir, $renderedPath));
        }
        $this->info('Read the file(s) above to inspect the render; the shared database was left untouched.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Floor>
     */
    private function resolveFloors(DungeonRoute $dungeonRoute): Collection
    {
        $floorIndex = $this->option('floor');

        if ($floorIndex !== null) {
            /** @var Collection<int, Floor> $floors */
            $floors = $dungeonRoute->dungeon->floors()->where('index', (int)$floorIndex)->get();

            return $floors;
        }

        // Mirror queueThumbnailRefresh: render exactly the floor(s) the map facade would show.
        return $dungeonRoute->dungeon
            ->floorsForMapFacade($dungeonRoute->mappingVersion, true)
            ->active()
            ->get();
    }
}
