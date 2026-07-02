<?php

namespace App\Console\Commands\Mapping;

use App\Service\Mapping\MappingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated The git-based mapping sync path (mapping:sync -> mapping:commit -> mapping:merge, scheduled
 *             every 5 minutes in routes/console.php) is deprecated and slated for removal. It regenerates
 *             the seeder JSON from the live DB and auto-commits/pushes to Git, which no longer matches how
 *             mapping data is managed. Tracked for removal in #3358.
 */
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:sync {--force=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs the current mapping with Github';

    /**
     * Execute the console command.
     */
    public function handle(MappingService $mappingService): int
    {
        Log::channel('scheduler')->debug('>> Synchronizing mapping');
        $force = (bool)$this->option('force');

        if ($mappingService->shouldSynchronizeMapping() || $force) {
            if ($this->call('mapping:save') === 0 &&
                $this->call('mapping:commit') === 0 &&
                $this->call('mapping:merge') === 0) {
                Log::channel('scheduler')->debug('Successfully synchronized mapping with Github!');
            }
        }

        Log::channel('scheduler')->debug('OK Synchronizing mapping');

        return 0;
    }
}
