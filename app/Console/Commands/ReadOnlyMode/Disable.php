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
class Disable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'readonly:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disables read-only mode - the site will once again accept all request types';

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
        if ($readOnlyModeService->setReadOnly(false)) {
            $this->info('Site is no longer read-only');

            return 0;
        } else {
            $this->error('Unable to disable read-only mode');

            return 1;
        }
    }
}
