<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RefreshDisplayIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:refreshdisplayids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the display ID of all NPCs that do not have them yet.';

    /**
     * Execute the console command.
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        /** @var Collection<Npc> $npcsToRefresh */
        $npcsToRefresh = Npc::with('dungeons')->whereNull('display_id')->get();

        $this->info(sprintf('Refreshing display_ids for %d npcs..', $npcsToRefresh->count()));

        foreach ($npcsToRefresh as $npc) {
            /** @var Dungeon $dungeon */
            foreach ($npc->dungeons as $dungeon) {
                foreach ($dungeon->getMappingVersionGameVersions() as $gameVersion) {
                    $displayId = $wowheadService->getNpcDisplayId($gameVersion, $npc);

                    // Sleep half a second, don't DDOS wowhead
                    usleep(500000);

                    if ($displayId !== null && $npc->update(['display_id' => $displayId])) {
                        $this->info(sprintf('- %s (%d): %d', __($npc->name), $npc->id, $displayId));
                        break 2;
                    } else {
                        $this->error(sprintf('- Failed to update %s (%d): %d', __($npc->name), $npc->id, $displayId));
                    }
                }
            }
        }
    }
}
