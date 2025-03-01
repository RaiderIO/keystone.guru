<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Spell\Spell;
use App\Service\Spell\SpellServiceInterface;
use Exception;
use Illuminate\Console\Command;

class FetchMissingSpells extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:fetchmissingspells';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches any missing spell that are assigned to NPCs, but don\'t exist in the Spells table.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(
        SpellServiceInterface $spellService
    ): int {
        $missingSpellIds = $spellService->getMissingSpellIds();

        $this->info(sprintf('Fetching spell data for %d spells', count($missingSpellIds)));

        $result = 0;
        foreach ($missingSpellIds as $spellId) {
            Spell::insert(['id' => $spellId]);

            // $this->call will return 0 if the command was successful, 1 if it failed
            $result = $result || $this->call('wowhead:fetchspelldata', ['--spellId' => $spellId]);
        }

        return $result;
    }
}
