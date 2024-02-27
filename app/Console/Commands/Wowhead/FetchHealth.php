<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;

class FetchHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:fetchhealth {dungeon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the health of all NPCs attached to said dungeon and saves it.';

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
     *
     * @throws Exception
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        $dungeon = Dungeon::where('key', $this->argument('dungeon'))->first();

        if ($dungeon === null) {
            throw new Exception('Unable to find dungeon!');
        }

        foreach ($dungeon->npcs as $npc) {
            if ($npc->dungeon_id === -1) {
                continue;
            } else if ($npc->base_health !== 12345) {
                $this->info(sprintf('Skipping already set health for %s (%d)', $npc->name, $npc->id));

                continue;
            }

            $this->info(sprintf('Fetching health for %s (%d)', $npc->name, $npc->id));

            $health = $wowheadService->getNpcHealth($dungeon->gameVersion, $npc);

            if ($health === 0) {
                $this->warn('- Unable to find health for npc!');
            } else {
                $npc->update(['base_health' => $health]);

                $this->info(sprintf('- %d', $health));
            }

            // Don't DDOS
            sleep(1);
        }
    }
}
