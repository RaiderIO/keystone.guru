<?php

namespace App\Console\Commands\Localization\Spell;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExportSpellNames extends Command
{
    use ExportsTranslations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:exportspellnames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Exports all current Spell names and saves them to the en_US/spells.php file.";

    public function handle(): int
    {
        // Start off with the existing array
        $export = __('spells', [], 'en_US');
        if (!is_array($export)) {
            $export = [];
        }

        Spell::chunk(1000, function (Collection $spells) use (&$export) {
            /** @var Collection<Spell> $spells */
            foreach ($spells as $spell) {
                if (empty($spell->name) || Str::startsWith($spell->name, ['spells.'])) {
                    continue;
                }

                $export[$spell->id] = $spell->name;
            }
        });

        ksort($export);

        return $this->exportTranslations('en_US', 'spells.php', $export) ? 0 : 1;
    }
}
