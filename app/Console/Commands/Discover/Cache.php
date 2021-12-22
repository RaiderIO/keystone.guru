<?php

namespace App\Console\Commands\Discover;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonServiceInterface;
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
     * @param SeasonServiceInterface $seasonService
     * @return int
     */
    public function handle(DiscoverServiceInterface $discoverService, SeasonServiceInterface $seasonService)
    {
        $this->info('Caching Discover pages');

        // Disable cache
        $discoverService = $discoverService->withCache(false);

        $currentSeason = $seasonService->getCurrentSeason();

        // Refresh caches for all categories
        foreach (Expansion::active()->get() as $expansion) {
            /** @var Expansion $expansion */
            $this->info(sprintf('- %s', $expansion->shortname));

            $discoverService = $discoverService->withExpansion($expansion);
            $discoverService->popular();
            $discoverService->new();
            $discoverService->popularGroupedByDungeon();
            $discoverService->popularUsers();

            foreach ($currentSeason->affixgroups as $affixgroup) {
                $this->info(sprintf('-- AffixGroup %s', $affixgroup->getTextAttribute()));
                $discoverService->popularGroupedByDungeonByAffixGroup($affixgroup);
            }

            foreach ($expansion->dungeons()->active()->get() as $dungeon) {
                /** @var Dungeon $dungeon */
                $this->info(sprintf('-- Dungeon %s', $dungeon->key));

                $discoverService->popularByDungeon($dungeon);
                $discoverService->newByDungeon($dungeon);
                $discoverService->popularUsersByDungeon($dungeon);

                foreach ($currentSeason->affixgroups as $affixgroup) {
//                    $this->info(sprintf('--- AffixGroup %s', $affixgroup->getTextAttribute()));
                    $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixgroup);
                    $discoverService->newByDungeonAndAffixGroup($dungeon, $affixgroup);
                    $discoverService->popularUsersByDungeonAndAffixGroup($dungeon, $affixgroup);
                }
            }
        }
        return 0;
    }
}
