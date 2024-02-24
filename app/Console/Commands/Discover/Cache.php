<?php

namespace App\Console\Commands\Discover;

use App\Jobs\RefreshDiscoverCache;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
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
    protected $signature = 'discover:cache {--async=}';

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
     * @return int
     */
    public function handle(DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService): int
    {
        $async = (bool)$this->option('async');

        if ($async) {
            $this->info('Caching Discover pages async');
            RefreshDiscoverCache::dispatch();

            return 0;
        }

        set_time_limit(3600);

        $this->info('Caching Discover pages');

        // Disable cache so that we may refresh it
        $discoverService = $discoverService->withCache(false);

        // Refresh caches for all categories
        foreach (Expansion::with(['dungeons'])->active()->get() as $expansion) {
            /** @var Expansion $expansion */
            $this->info(sprintf('- %s', $expansion->shortname));

            // First we will parse all pages for a certain expansion (just let me see all dungeons for an expansion)
            $discoverService = $discoverService->withExpansion($expansion);
            $discoverService->popular();
            $discoverService->new();
            $discoverService->popularGroupedByDungeon();
            $discoverService->popularUsers();

            // In theory this can lead to cache misses when we're in the process of switching seasons, but I'll take it
            $currentSeason = $expansionService->getCurrentSeason($expansion, GameServerRegion::getUserOrDefaultRegion());

            foreach ($expansion->dungeons()->active()->get() as $dungeon) {
                /** @var Dungeon $dungeon */
                $this->info(sprintf('-- Dungeon %s', $dungeon->key));

                $discoverService->popularByDungeon($dungeon);
                $discoverService->newByDungeon($dungeon);
                $discoverService->popularUsersByDungeon($dungeon);

                foreach (optional($currentSeason)->affixgroups ?? [] as $affixGroup) {
//                    $this->info(sprintf('--- AffixGroup %s', $affixgroup->getTextAttribute()));
                    $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixGroup);
                    $discoverService->newByDungeonAndAffixGroup($dungeon, $affixGroup);
                    $discoverService->popularUsersByDungeonAndAffixGroup($dungeon, $affixGroup);
                }
            }

            // Now, if this expansion has a current season, re-build all the pages as if they're viewing the
            // :expansion/season/:season page. Remember, an expansion's season can have dungeons from any other expansion into it
            // The cache key changes when you assign a season to the DiscoverService so those pages need to be cached again
            if ($currentSeason !== null) {
                foreach ($currentSeason->affixgroups ?? [] as $affixGroup) {
                    $this->info(sprintf('-- AffixGroup %s', $affixGroup->getTextAttribute()));
                    $discoverService->popularGroupedByDungeonByAffixGroup($affixGroup);
                }

                $this->info(sprintf('-- %s', $currentSeason->name));
                $discoverService = $discoverService->withSeason($currentSeason);
                foreach ($currentSeason->dungeons()->active()->get() as $dungeon) {
                    $this->info(sprintf('--- Dungeon %s', $dungeon->key));

                    $discoverService->popularByDungeon($dungeon);
                    $discoverService->newByDungeon($dungeon);
                    $discoverService->popularUsersByDungeon($dungeon);

                    foreach ($currentSeason->affixgroups ?? [] as $affixGroup) {
                        $this->info(sprintf('--- AffixGroup %s', $affixGroup->getTextAttribute()));
                        $discoverService->popularGroupedByDungeonByAffixGroup($affixGroup);
                    }
                }
            }

            // Reset for the next iteration
            $discoverService = $discoverService->withSeason(null);
        }

        return 0;
    }
}
