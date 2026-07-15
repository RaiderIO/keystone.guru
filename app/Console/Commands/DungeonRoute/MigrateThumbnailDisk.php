<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MigrateThumbnailDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:migratethumbnaildisk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dev-only: moves dungeon route thumbnail files (and their File rows) from the local disk to the public disk.';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command must not be run in production.');

            return 1;
        }

        /** @var Collection<int, File> $files */
        $files = File::query()
            ->where('model_class', DungeonRouteThumbnail::class)
            ->where('disk', 'local')
            ->get();

        $this->info(sprintf('Found %d thumbnail file(s) on the local disk.', $files->count()));

        $migrated = 0;
        foreach ($files as $file) {
            $path = ltrim($file->path, '/');

            if (!Storage::disk('local')->exists($path)) {
                $this->warn(sprintf('File #%d: %s does not exist on the local disk, skipping.', $file->id, $path));

                continue;
            }

            Storage::disk('public')->put($path, Storage::disk('local')->get($path));

            $file->update([
                'disk' => 'public',
                'path' => $path,
            ]);

            $migrated++;
        }

        $this->info(sprintf('Migrated %d file(s) to the public disk.', $migrated));

        return 0;
    }
}
