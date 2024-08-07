<?php

namespace App\Console\Commands\ReadOnlyMode;

use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Illuminate\Console\Command;

/**
 * Class Aggregate
 *
 * @author Wouter
 *
 * @since 16/02/2023
 */
class Enable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'readonly:enable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Puts the site in read-only mode - no longer allowing the acceptance of requests other than GET requests';

    /**
     * Execute the console command.
     */
    public function handle(ReadOnlyModeServiceInterface $readOnlyModeService): int
    {
        if ($readOnlyModeService->setReadOnly(true)) {
            $this->info('Site is now read-only');

            return 0;
        } else {
            $this->error('Unable to put site in read-only mode');

            return 1;
        }
    }
}
