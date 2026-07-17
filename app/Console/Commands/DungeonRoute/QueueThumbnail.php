<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Console\Command;

class QueueThumbnail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:queuethumbnail {publicKey : The public key of the dungeon route} {--force : Regenerate even if the thumbnail is up to date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Path B: queues a route thumbnail refresh onto the shared queue for Horizon (the only Chrome-capable container) to render to the public disk, visible to all stacks. See the generating-thumbnails skill.';

    public function handle(ThumbnailServiceInterface $thumbnailService): int
    {
        /** @var DungeonRoute|null $dungeonRoute */
        $dungeonRoute = DungeonRoute::where('public_key', $this->argument('publicKey'))->first();

        if ($dungeonRoute === null) {
            $this->error(sprintf('No dungeon route found with public key %s.', $this->argument('publicKey')));

            return self::FAILURE;
        }

        $queued = $thumbnailService->queueThumbnailRefresh($dungeonRoute, (bool)$this->option('force'));

        if (!$queued) {
            $this->warn(sprintf('No thumbnail jobs were queued for %s (it may have no mapping version).', $dungeonRoute->public_key));

            return self::SUCCESS;
        }

        $this->info(sprintf('Queued thumbnail refresh for %s; Horizon on the main stack will render it shortly.', $dungeonRoute->public_key));

        return self::SUCCESS;
    }
}
