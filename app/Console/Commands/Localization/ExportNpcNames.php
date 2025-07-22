<?php

namespace App\Console\Commands\Localization;

use App\Console\Commands\Localization\Traits\ExportsNpcNames;
use App\Models\Npc\Npc;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExportNpcNames extends Command
{
    use ExportsNpcNames;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:exportnpcnames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Exports all current NPC names and saves them to the en_US/npcs.php file.";

    public function handle(): int
    {

        $export = [];
        Npc::chunk(1000, function (Collection $npcs) use (&$export) {
            /** @var Collection<Npc> $npcs */
            foreach ($npcs as $npc) {
                if (empty($npc->name) || Str::startsWith($npc->name, 'Unknown')) {
                    continue;
                }

                $export[$npc->id] = $npc->name;
            }
        });

        ksort($export);

        return $this->exportNpcNames('en_US', $export) ? 0 : 1;
    }
}
