<?php

namespace App\Console\Commands\Localization\Npc;

use App\Models\Npc\Npc;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportNpcNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:importnpcnames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Imports all NPC names from the en_US/npcs.php file and updates the database's NPCs with translation keys.";

    public function handle(): int
    {
        $npcNames = __('npcs', [], 'en_US');

        if (!is_array($npcNames) || empty($npcNames)) {
            $this->error('No NPC names found in en_US/npcs.php');

            return 1;
        }

        $updatedCount = 0;
        $progressBar  = $this->output->createProgressBar(Npc::count());
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);
        Npc::chunk(1000, function (Collection $npcs) use (&$npcNames, &$updatedCount, $progressBar) {
            /** @var Collection<Npc> $npcs */
            foreach ($npcs as $npc) {
                if (empty($npc->name) || Str::startsWith($npc->name, 'npcs.')) {
                    continue;
                }

                if (isset($npcNames[$npc->id])) {
                    $npc->update([
                        'name' => sprintf('npcs.%d', $npc->id),
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
