<?php

namespace App\Console\Commands\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc;
use App\Service\Wowhead\WowheadServiceInterface;
use App\Service\WowTools\WowToolsServiceInterface;
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
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        /** @var Collection<Npc> $npcsToRefresh */
        $npcsToRefresh = Npc::with('dungeon')->whereNull('display_id')->get();

        $this->info(sprintf('Refreshing display_ids for %d npcs..', $npcsToRefresh->count()));

        /** @var GameVersion $retail */
        $retail = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        foreach ($npcsToRefresh as $npc) {
            $gameVersion = $npc->dungeon?->gameVersion ?? $retail;
            $displayId = $wowheadService->getNpcDisplayId($gameVersion, $npc);

            if ($displayId !== null && $npc->update(['display_id' => $displayId])) {
                $this->info(sprintf('- %s (%d): %d', $npc->name, $npc->id, $displayId));
            } else {
                $this->error(sprintf('- Failed to update %s (%d): %d', $npc->name, $npc->id, $displayId));
            }

            // Sleep half a second, don't DDOS wowhead
            usleep(500000);
        }
    }
}
