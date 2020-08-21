<?php

use App\Service\Cache\CacheService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param CacheService $cacheService
     * @return void
     */
    public function run(CacheService $cacheService)
    {
        $cacheService->dropCaches();
        // $this->call(UsersTableSeeder::class);
        // $this->call(LaratrustSeeder::class);

        // Seeders which don't depend on anything else
        $this->call(GameServerRegionsSeeder::class);
        $this->call(ExpansionsSeeder::class);
        $this->call(DungeonsSeeder::class);
        $this->call(RouteAttributesSeeder::class);
        $this->call(PaidTiersSeeder::class);
        $this->call(FactionsSeeder::class);
        $this->call(NpcClassificationsSeeder::class);
        $this->call(NpcClassesSeeder::class);
        $this->call(NpcTypesSeeder::class);
        $this->call(RaidMarkersSeeder::class);
        $this->call(ReleaseChangelogCategorySeeder::class);
        $this->call(ReleasesSeeder::class);
        $this->call(MapIconTypesSeeder::class);

        // Depends on SeasonsSeeder
        $this->call(SeasonsSeeder::class);
        $this->call(AffixSeeder::class);

        //  Depends on Factions
        $this->call(CharacterInfoSeeder::class);

        // Depends on Expansions
        $this->call(DungeonDataSeeder::class);
    }
}
