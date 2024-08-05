<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Models\Spell\Spell;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;

class FetchSpellData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:fetchspelldata {--dungeon=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches spell data from Wowhead that is missing from the database.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        $dungeonKey = $this->option('dungeon');

        if ($dungeonKey !== null) {
            /** @var Dungeon $dungeon */
            $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();

            $spells = $dungeon->spells;
        } else {
            $spells = Spell::where('name', '')->get();
        }

        $this->info(sprintf('Fetching spell data for %d spells', $spells->count()));

        foreach ($spells as $spell) {
            $this->info(sprintf('Fetching spell data for spell %d', $spell->id));

            $spellDataResult = $wowheadService->getSpellData($spell->id);

            if ($spellDataResult === null) {
                $this->warn('- Unable to find spell data for spell!');
            } else {
                $spellAttributes = $spellDataResult->toArray();
                $spell->update($spellAttributes);

                $this->info(sprintf('- %s', $spellDataResult->getName()));
                foreach (array_filter($spellAttributes) as $key => $value) {
                    $this->comment(sprintf('-- %s: %s', $key, $value));
                }
            }

            // Don't DDOS
//            usleep(500000);
        }
    }
}
