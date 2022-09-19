<?php

namespace Database\Seeders;

use App\Service\Cache\CacheServiceInterface;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param CacheServiceInterface $cacheService
     * @return void
     */
    public function run(CacheServiceInterface $cacheService)
    {
        $cacheService->dropCaches();
        // $this->call(UsersTableSeeder::class);
        // $this->call(LaratrustSeeder::class);

        // Seeders which don't depend on anything else
        $this->call(GameServerRegionsSeeder::class);
        $this->call(ExpansionsSeeder::class);
        $this->call(RouteAttributesSeeder::class);
        $this->call(PatreonBenefitsSeeder::class);
        $this->call(FactionsSeeder::class);
        $this->call(NpcClassificationsSeeder::class);
        $this->call(NpcClassesSeeder::class);
        $this->call(NpcTypesSeeder::class);
        $this->call(RaidMarkersSeeder::class);
        $this->call(ReleaseChangelogCategorySeeder::class);
        $this->call(ReleasesSeeder::class);
        $this->call(MapIconTypesSeeder::class);
        $this->call(TagCategorySeeder::class);
        $this->call(PublishedStatesSeeder::class);

        // Depends on ExpansionsSeeder, SeasonsSeeder
        $this->call(AffixSeeder::class);

        // Depends on SeasonsSeeder, AffixSeeder
        $this->call(TimewalkingEventSeeder::class);

        //  Depends on Factions
        $this->call(CharacterInfoSeeder::class);

        // Depends on Expansions
        $this->call(DungeonDataSeeder::class);

        // Depends on ExpansionsSeeder, DungeonDataSeeder
        $this->call(SeasonsSeeder::class);
    }
}
