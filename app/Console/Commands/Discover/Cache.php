<?php

namespace App\Console\Commands\Discover;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use Illuminate\Console\Command;

class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discover:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches all search results for routes for the route discovery page';

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
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return int
     */
    public function handle(DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
        $this->info('Caching Discover pages');

        // Disable cache so that we may refresh it
        $discoverService = $discoverService->withCache(false);

        // Refresh caches for all categories
        foreach (Expansion::active()->get() as $expansion) {
            /** @var Expansion $expansion */
            $this->info(sprintf('- %s', $expansion->shortname));

            $discoverService = $discoverService->withExpansion($expansion);
            $discoverService->popular();
            $discoverService->new();
            $discoverService->popularGroupedByDungeon();
            $discoverService->popularUsers();

            $currentSeason = $expansionService->getCurrentSeason($expansion);
            foreach ($currentSeason->affixgroups as $affixGroup) {
                $this->info(sprintf('-- AffixGroup %s', $affixGroup->getTextAttribute()));
                $discoverService->popularGroupedByDungeonByAffixGroup($affixGroup);
            }

            foreach ($expansion->dungeons()->active()->get() as $dungeon) {
                /** @var Dungeon $dungeon */
                $this->info(sprintf('-- Dungeon %s', $dungeon->key));

                $discoverService->popularByDungeon($dungeon);
                $discoverService->newByDungeon($dungeon);
                $discoverService->popularUsersByDungeon($dungeon);

                foreach ($currentSeason->affixgroups as $affixGroup) {
//                    $this->info(sprintf('--- AffixGroup %s', $affixgroup->getTextAttribute()));
                    $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixGroup);
                    $discoverService->newByDungeonAndAffixGroup($dungeon, $affixGroup);
                    $discoverService->popularUsersByDungeonAndAffixGroup($dungeon, $affixGroup);
                }
            }
        }
        return 0;
    }
}
