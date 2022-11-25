<?php

namespace App\Console\Commands\WowTools;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Npc;
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
    protected $signature = 'wowtools:refreshdisplayids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the display ID of all NPCs that do not have them yet.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(WowToolsServiceInterface $wowToolsService)
    {
        /** @var Collection|Npc[] $npcsToRefresh */
        $npcsToRefresh = Npc::whereNull('display_id')->get();

        $this->info(sprintf('Refreshing display_ids for %d npcs..', $npcsToRefresh->count()));

        foreach ($npcsToRefresh as $npc) {
            $displayId = $wowToolsService->getDisplayId($npc->id);

            if ($displayId !== null && $npc->update(['display_id' => $displayId])) {
                $this->info(sprintf('- %s (%d): %d', $npc->name, $npc->id, $displayId));
            } else {
                $this->error(sprintf('- Failed to update %s (%d): %d', $npc->name, $npc->id, $displayId));
            }

            // Sleep half a second, don't DDOS wow.tools
            usleep(500000);
        }
    }
}
