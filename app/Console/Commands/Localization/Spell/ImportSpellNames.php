<?php

namespace App\Console\Commands\Localization\Spell;

use App\Models\Spell\Spell;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportSpellNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:importspellnames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Imports all spell names from the en_US/spells.php file and updates the database's spells with translation keys.";

    public function handle(): int
    {
        $spellNames = __('spells', [], 'en_US');

        if (!is_array($spellNames) || empty($spellNames)) {
            $this->error('No Spell names found in en_US/spells.php');

            return 1;
        }

        $updatedCount = 0;
        $progressBar  = $this->output->createProgressBar(Spell::count());
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);
        Spell::chunk(1000, function (Collection $spells) use (&$spellNames, &$updatedCount, $progressBar) {
            /** @var Collection<Spell> $spells */
            foreach ($spells as $spell) {
                if (empty($spell->name) || Str::startsWith($spell->name, ['spells.'])) {
                    continue;
                }

                if (isset($spellNames[$spell->id])) {
                    $spell->update([
                        'name' => sprintf('spells.%d', $spell->id),
                    ]);
                    $updatedCount++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();

        $this->output->writeln('');

        return 0;
    }
}
