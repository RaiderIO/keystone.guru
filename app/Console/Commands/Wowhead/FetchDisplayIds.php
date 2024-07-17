<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;

class FetchDisplayIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:fetchdisplayids {dungeon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the display IDs of all NPCs attached to said dungeon and updates it.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::firstWhere('key', $this->argument('dungeon'));

        if ($dungeon === null) {
            throw new Exception('Unable to find dungeon!');
        }

        foreach ($dungeon->npcs as $npc) {
            if ($npc->dungeon_id === -1) {
                continue;
            } else if ($npc->display_id !== null) {
                $this->info(sprintf('Skipping already set display ID for %s (%d)', $npc->name, $npc->id));

                continue;
            }

            $this->info(sprintf('Fetching display ID for %s (%d)', $npc->name, $npc->id));

            $displayId = $wowheadService->getNpcDisplayId($dungeon->gameVersion, $npc);

            if ($displayId === null) {
                $this->warn('- Unable to find display ID for npc!');
            } else {
                $npc->update(['display_id' => $displayId]);

                $this->info(sprintf('- %d', $displayId));
            }

            // Don't DDOS
            sleep(1);
        }
    }
}
