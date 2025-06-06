<?php

namespace App\Console\Commands\Scheduler\Discover;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Jobs\RefreshDiscoverCache;
use App\Models\Expansion;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;

class Cache extends SchedulerCommand
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
     * Execute the console command.
     */
    public function handle(DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService): int
    {
        return $this->trackTime(function () use ($discoverService, $expansionService) {

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

            $expansions = array_filter([
                $expansionService->getCurrentExpansion(),
                // Next expansion is usually going to be empty, unless we're actively approaching a new expansion
                $expansionService->getNextExpansion(),
            ]);

            // Refresh caches for all categories
            foreach ($expansions as $expansion) {
                /** @var Expansion $expansion */
                $this->info(sprintf('- %s', $expansion->shortname));

                $expansion->load('dungeonsAndRaids');

                // First we will parse all pages for a certain expansion (just let me see all dungeons for an expansion)
                $discoverService = $discoverService->withExpansion($expansion);
                $discoverService->popular();
                $discoverService->new();
                $discoverService->popularGroupedByDungeon();
                $discoverService->popularUsers();

                // In theory this can lead to cache misses when we're in the process of switching seasons, but I'll take it
                /** @var Season[] $seasons */
                $seasons = array_filter([
                    $expansionService->getCurrentSeason($expansion),
                    $expansionService->getNextSeason($expansion),
                ]);

                // Now, if this expansion has a current season, re-build all the pages as if they're viewing the
                // :expansion/season/:season page. Remember, an expansion's season can have dungeons from any other expansion into it
                // The cache key changes when you assign a season to the DiscoverService so those pages need to be cached again
                foreach ($seasons as $season) {
                    $this->info(sprintf('- %s', $season->name));

//                    foreach ($season->affixGroups ?? [] as $affixGroup) {
//                        $this->info(sprintf('-- AffixGroup %s', $affixGroup->getTextAttribute()));
//                        $discoverService->popularGroupedByDungeonByAffixGroup($affixGroup);
//                    }

                    $discoverService->popularBySeason($season);
                    $discoverService->newBySeason($season);

                    $discoverService = $discoverService->withSeason($season);
                    foreach ($season->dungeons()->active()->get() as $dungeon) {
                        $this->info(sprintf('-- Dungeon %s', $dungeon->key));

                        $discoverService->popularByDungeon($dungeon);
                        $discoverService->newByDungeon($dungeon);
                        $discoverService->popularUsersByDungeon($dungeon);

//                        foreach ($season->affixGroups ?? [] as $affixGroup) {
//                            $this->info(sprintf('--- AffixGroup %s', $affixGroup->getTextAttribute()));
//                            $discoverService->popularGroupedByDungeonByAffixGroup($affixGroup);
//                        }
                    }
                }

                // Reset for the next iteration
                $discoverService = $discoverService->withSeason(null);
            }

            return 0;
        });
    }
}
