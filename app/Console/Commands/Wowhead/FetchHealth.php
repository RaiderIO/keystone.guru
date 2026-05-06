<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Models\Npc\NpcHealth;
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
            foreach ($dungeon->getMappingVersionGameVersions() as $gameVersion) {
                if ($npc->getHealthByGameVersion($gameVersion) !== null) {
                    $this->info(sprintf('Skipping already set health for %s (%d)', __($npc->name), $npc->id));

                    continue;
                }

                // Don't DDOS
                sleep(1);

                $this->info(sprintf('Fetching health for %s (%d)', __($npc->name), $npc->id));

                $health = $wowheadService->getNpcHealth($gameVersion, $npc);

                if (empty($health)) {
                    $this->warn('- Unable to find health for npc!');
                } else {
                    NpcHealth::insert([
                        'npc_id'          => $npc->id,
                        'game_version_id' => $gameVersion->id,
                        'health'          => $health,
                    ]);

                    $this->info(sprintf('- %d', $health));
                    break;
                }
            }
        }
    }
}
