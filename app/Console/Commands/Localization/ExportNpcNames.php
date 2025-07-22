<?php

namespace App\Console\Commands\Localization;

use App\Models\Npc\Npc;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExportNpcNames extends Command
{
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

        $exportToString = var_export($export, true);
        $exportToString = preg_replace_callback(
            '/\b(\d+)\s*=>/',
            function ($matches) {
                return "'" . $matches[1] . "' =>";
            },
            $exportToString
        );

        if (file_put_contents(
            lang_path('en_US/npcs.php'),
            '<?php ' . PHP_EOL . PHP_EOL . 'return ' . $exportToString . ';'
        )) {
            $this->info('NPC names exported successfully to en_US/npcs.php');

            return 0;
        } else {
            $this->error('Failed to write NPC names to en_US/npcs.php');

            return 1;
        }
    }
}
