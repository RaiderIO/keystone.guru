<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        $this->call(LaratrustSeeder::class);

        // Seeders which don't depend on anything else
        $this->call(GameServerRegionSeeder::class);
        $this->call(ExpansionsSeeder::class);
        $this->call(DungeonsSeeder::class);
        $this->call(RouteAttributesSeeder::class);
        $this->call(PaidTiersSeeder::class);
        $this->call(FactionsSeeder::class);
        $this->call(AffixSeeder::class);
        $this->call(NpcClassificationSeeder::class);
        $this->call(RaidMarkerSeeder::class);

        //  Depends on Factions
        $this->call(CharacterInfoSeeder::class);

        // Depends on Expansions
        $this->call(DungeonDataSeeder::class);
    }
}
