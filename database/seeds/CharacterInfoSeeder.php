<?php

use Illuminate\Database\Seeder;
use App\Models\CharacterRace;
use App\Models\CharacterClass;
use App\Models\CharacterRaceClassCoupling;
use App\Models\CharacterSpecialization;

class CharacterInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Adding known races');

        $classes = [new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            new CharacterRace(['name' => 'Human']),
            ];

        foreach($classes as $class){
            /** @var $class \Illuminate\Database\Eloquent\Model */
            $class->save();
        }

        $this->command->info('Adding known classes');

        $classes = [new CharacterClass(['name' => 'Warrior', 'color' => '#C79C6E']),
            new CharacterClass(['name' => 'Paladin', 'color' => '#F58CBA']),
            new CharacterClass(['name' => 'Hunter', 'color' => '#ABD473']),
            new CharacterClass(['name' => 'Rogue', 'color' => '#FFF569']),
            new CharacterClass(['name' => 'Priest', 'color' => '#FFFFFF']),
            new CharacterClass(['name' => 'Death Knight', 'color' => '#C41F3B']),
            new CharacterClass(['name' => 'Shaman', 'color' => '#0070DE']),
            new CharacterClass(['name' => 'Mage', 'color' => '#69CCF0']),
            new CharacterClass(['name' => 'Warlock', 'color' => '#9482C9']),
            new CharacterClass(['name' => 'Monk', 'color' => '#00FF96']),
            new CharacterClass(['name' => 'Druid', 'color' => '#FF7D0A']),
            new CharacterClass(['name' => 'Demon Hunter', 'color' => '#A330C9'])];

        foreach($classes as $class){
            /** @var $class \Illuminate\Database\Eloquent\Model */
            $class->save();
        }

//        $legion = new App\Models\Expansion([
//            'name' => 'Legion',
//            'color' => '#27ff0f'
//        ]);
    }

    private function _rollback(){
        DB::table('character_classes')->truncate();
        DB::table('character_specializations')->truncate();
    }
}
