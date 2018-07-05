<?php

use Illuminate\Database\Seeder;
use App\Models\CharacterRace;
use App\Models\CharacterClass;
use App\Models\CharacterRaceClassCoupling;
use App\Models\CharacterSpecialization;
use App\Models\File;

class CharacterInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known races');

        // Do the name as key => value so we can easily fetch it later on
        $races = ['Human' => new CharacterRace(['faction' => 'Alliance']),
            'Dwarf' => new CharacterRace(['faction' => 'Alliance']),
            'Night Elf' => new CharacterRace(['faction' => 'Alliance']),
            'Gnome' => new CharacterRace(['faction' => 'Alliance']),
            'Draenei' => new CharacterRace(['faction' => 'Alliance']),
            'Worgen' => new CharacterRace(['faction' => 'Alliance']),
            'PandarenAlliance' => new CharacterRace(['faction' => 'Alliance']),
            'Void Elf' => new CharacterRace(['faction' => 'Alliance']),
            'Lightforged Draenei' => new CharacterRace(['faction' => 'Alliance']),
            'Dark Iron Dwarf' => new CharacterRace(['faction' => 'Alliance']),
            'Kul Tiran Human' => new CharacterRace(['faction' => 'Alliance']),


            'Orc' => new CharacterRace(['faction' => 'Horde']),
            'Undead' => new CharacterRace(['faction' => 'Horde']),
            'Tauren' => new CharacterRace(['faction' => 'Horde']),
            'Troll' => new CharacterRace(['faction' => 'Horde']),
            'Blood Elf' => new CharacterRace(['faction' => 'Horde']),
            'Goblin' => new CharacterRace(['faction' => 'Horde']),
            'PandarenHorde' => new CharacterRace(['faction' => 'Horde']),
            'Nightborne' => new CharacterRace(['faction' => 'Horde']),
            'Highmountain Tauren' => new CharacterRace(['faction' => 'Horde']),
            'Mag\'har Orc' => new CharacterRace(['faction' => 'Horde']),
            'Zandalari Troll' => new CharacterRace(['faction' => 'Horde']),
        ];

        foreach ($races as $name => $race) {
            $race->name = $name;
            // Pesky Pandaren
            $race->name = str_replace('Alliance', '', $race->name);
            $race->name = str_replace('Horde', '', $race->name);
            /** @var $race \Illuminate\Database\Eloquent\Model */
            $race->save();
        }

        $this->command->info('Adding known classes');

        $classes = [new CharacterClass(['name' => 'Warrior', 'color' => '#C79C6E']),
            new CharacterClass(['name' => 'Hunter', 'color' => '#ABD473']),
            new CharacterClass(['name' => 'Death Knight', 'color' => '#C41F3B']),
            new CharacterClass(['name' => 'Mage', 'color' => '#69CCF0']),
            new CharacterClass(['name' => 'Priest', 'color' => '#FFFFFF']),
            new CharacterClass(['name' => 'Monk', 'color' => '#00FF96']),
            new CharacterClass(['name' => 'Rogue', 'color' => '#FFF569']),
            new CharacterClass(['name' => 'Warlock', 'color' => '#9482C9']),
            new CharacterClass(['name' => 'Shaman', 'color' => '#0070DE']),
            new CharacterClass(['name' => 'Paladin', 'color' => '#F58CBA']),
            new CharacterClass(['name' => 'Druid', 'color' => '#FF7D0A']),
            new CharacterClass(['name' => 'Demon Hunter', 'color' => '#A330C9'])];

        foreach ($classes as $race) {
            // Temp file
            $race->icon_file_id = -1;
            /** @var $race \Illuminate\Database\Eloquent\Model */
            $race->save();

            $iconName = strtolower(str_replace(' ', '', $race->name));
            $icon = new File();
            $icon->model_id = $race->id;
            $icon->model_class = get_class($race);
            $icon->disk = 'public';
            $icon->path = sprintf('classes/%s.png', $iconName);
            $icon->save();

            $race->icon_file_id = $icon->id;
            $race->save();
        }

        $this->command->info('Adding known race/class combinations');
        // @formatter:off
        $matrix = [
            'Human' =>                  ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' '],
            'Dwarf' =>                  ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' '],
            'Night Elf' =>              ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', 'x', 'x'],
            'Gnome' =>                  ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Draenei' =>                ['x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', 'x', 'x', ' ', ' '],
            'Worgen' =>                 ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', ' '],
            'Void Elf' =>               ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Lightforged Draenei' =>    ['x', 'x', ' ', 'x', 'x', ' ', ' ', ' ', ' ', 'x', ' ', ' '],
            'Dark Iron Dwarf' =>        ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' '],
            'Kul Tiran Human' =>        [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', 'x', ' '],

            'PandarenAlliance' =>       ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],
            'PandarenHorde' =>          ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],

            'Orc' =>                    ['x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', ' ', ' '],
            'Undead' =>                 ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Tauren' =>                 ['x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', 'x', 'x', ' '],
            'Troll' =>                  ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' '],
            'Blood Elf' =>              ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x'],
            'Goblin' =>                 ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],
            'Nightborne' =>             ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Highmountain Tauren' =>    ['x', 'x', ' ', ' ', ' ', 'x', ' ', ' ', 'x', ' ', 'x', ' '],
            'Mag\'har Orc' =>           ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],
            'Zandalari Troll' =>        ['x', 'x', ' ', 'x', 'x', ' ', 'x', 'x', 'x', ' ', 'x', ' '],
        ];
        // @formatter:on

        foreach ($matrix as $raceStr => $raceClasses) {
            $race = $races[$raceStr];
            $i = 0;
            foreach ($raceClasses as $raceClass) {
                if ($raceClass === 'x') {
                    $class = $classes[$i];

                    $raceClassCoupling = new CharacterRaceClassCoupling();
                    $raceClassCoupling->character_race_id = $race->id;
                    $raceClassCoupling->character_class_id = $class->id;

                    $raceClassCoupling->save();

                }
                $i++;
            }
        }
    }

    private function _rollback()
    {
        DB::table('character_races')->truncate();
        DB::table('character_classes')->truncate();
        DB::table('character_class_specializations')->truncate();
        DB::table('character_race_class_couplings')->truncate();
        DB::table('files')->where('model_class', '=', 'App\Models\CharacterClass')->delete();
    }
}
