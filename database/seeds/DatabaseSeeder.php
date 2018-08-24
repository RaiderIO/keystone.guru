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

        $this->call(ExpansionsSeeder::class);
        $this->call(DungeonsSeeder::class);

        $this->call(FactionsSeeder::class);
        $this->call(CharacterInfoSeeder::class);

        $this->call(AffixSeeder::class);

        $this->call(NpcClassificationSeeder::class);

        $this->call(DungeonDataSeeder::class);
    }
}
