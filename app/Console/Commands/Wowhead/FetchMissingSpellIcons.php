<?php

namespace App\Console\Commands\Wowhead;

use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;

class FetchMissingSpellIcons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:fetchmissingspellicons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches any missing spell icons from Wowhead.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(WowheadServiceInterface $wowheadService): int
    {
        return $wowheadService->downloadMissingSpellIcons() ? 0 : 1;
    }
}
