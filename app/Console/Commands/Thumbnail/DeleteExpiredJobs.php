<?php

namespace App\Console\Commands\Thumbnail;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DeleteExpiredJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnail:deleteexpiredjobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes any expired thumbnail jobs from the database.';

    public function handle(): int
    {
        $count = 0;

        DungeonRouteThumbnailJob::where('status', '<>', DungeonRouteThumbnailJob::STATUS_EXPIRED)
            ->where('created_at', '<', Carbon::now()->subSeconds(
                config('keystoneguru.api.dungeon_route.thumbnail.expiration_time_seconds')
            ))->chunk(100, static function (Collection $rows) use (&$count) {
                /** @var Collection|DungeonRouteThumbnailJob[] $rows */
                foreach ($rows as $row) {
                    $row->expire();
                }
                $count += $rows->count();
            });

        $this->info(sprintf('Cleaned up %d thumbnail jobs', $count));

        return 0;
    }
}
