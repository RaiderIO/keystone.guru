<?php

namespace App\Console\Commands\Spell;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Service\Spell\SpellServiceInterface;
use App\Service\Traits\Curl;
use Illuminate\Console\Command;

class ImportCsv extends Command
{
    use Curl;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spell:importcsv {relativePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import spells from a CSV file.';

    /**
     * Execute the console command.
     */
    public function handle(SpellServiceInterface $spellService): int
    {
        $csvPath = base_path($this->argument('relativePath'));

        if (!file_exists($csvPath)) {
            $this->error(sprintf('Unable to find file %s', $csvPath));

            return -1;
        }

        return $spellService->importFromCsv($csvPath) ? 0 : -1;
    }
}
