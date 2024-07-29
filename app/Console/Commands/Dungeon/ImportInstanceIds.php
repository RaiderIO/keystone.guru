<?php

namespace App\Console\Commands\Dungeon;

use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Console\Command;

class ImportInstanceIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeon:importinstanceids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports instance IDs from the JournalInstance.csv import.';

    public function handle(
        DungeonServiceInterface $dungeonService
    ): int {
        $dungeonService->importInstanceIdsFromCsv(
            'JournalInstance.csv'
        );

        return 0;
    }
}
