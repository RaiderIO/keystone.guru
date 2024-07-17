<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Models\Spell;
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
    protected $signature = 'wowhead:fetchspelldata';

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
//        foreach (Spell::where('name', '')->get() as $spell) {
        foreach (Spell::where('id', 589)->get() as $spell) {
            $this->info(sprintf('Fetching spell data for spell %d', $spell->id));

            $spellDataResult = $wowheadService->getSpellData($spell->id);

            if ($spellDataResult === null) {
                $this->warn('- Unable to find spell data for spell!');
            } else {
                $spell->update([
                    'icon_name' => $spellDataResult->getIconName(),
                    'name'      => $spellDataResult->getName(),
                ]);

                $this->info(sprintf('- %s', $spellDataResult->getName()));
                $this->comment(sprintf('-- Icon name: %s', $spellDataResult->getIconName()));
            }

            // Don't DDOS
            sleep(1);
        }
    }
}
