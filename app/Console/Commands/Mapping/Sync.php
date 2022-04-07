<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Service\Mapping\MappingService;
use Illuminate\Console\Command;

class Sync extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:sync';

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
        logger()->debug('>> Synchronizing mapping');

        if ($mappingService->shouldSynchronizeMapping()) {
            if ($this->call('mapping:save') === 0 &&
                $this->call('mapping:commit') === 0 &&
                $this->call('mapping:merge') === 0) {

                logger()->debug('Successfully synchronized mapping with Github!');
            }
        }

        logger()->debug('OK Synchronizing mapping');

        return 0;
    }
}
