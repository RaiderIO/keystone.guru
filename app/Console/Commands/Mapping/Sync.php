<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Service\Mapping\MappingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Sync extends Command
{
    use ExecutesShellCommands;

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
     *
     * @return int
     */
    public function handle(MappingService $mappingService)
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
