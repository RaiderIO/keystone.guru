<?php

namespace App\Console\Commands\ReadOnlyMode;

use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Illuminate\Console\Command;

/**
 * Class Aggregate
 * @package App\Console\Commands\Localization
 * @author Wouter
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ReadOnlyModeServiceInterface $readOnlyModeService)
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
