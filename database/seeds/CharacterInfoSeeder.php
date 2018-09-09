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
     * @throws Exception
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known races');

        $alliance_id = \App\Models\Faction::all()->where('name', '=', 'Alliance')->first()->id;
        $horde_id = \App\Models\Faction::all()->where('name', '=', 'Horde')->first()->id;

        if ($alliance_id === 0 || $horde_id === 0) {
            throw new Exception('Unable to find factions');
        }

        // Do the name as key => value so we can easily fetch it later on
        $races = ['Human' => new CharacterRace(['faction_id' => $alliance_id]),
            'Dwarf' => new CharacterRace(['faction_id' => $alliance_id]),
            'Night Elf' => new CharacterRace(['faction_id' => $alliance_id]),
            'Gnome' => new CharacterRace(['faction_id' => $alliance_id]),
            'Draenei' => new CharacterRace(['faction_id' => $alliance_id]),
            'Worgen' => new CharacterRace(['faction_id' => $alliance_id]),
            'PandarenAlliance' => new CharacterRace(['faction_id' => $alliance_id]),
            'Void Elf' => new CharacterRace(['faction_id' => $alliance_id]),
            'Lightforged Draenei' => new CharacterRace(['faction_id' => $alliance_id]),
            'Dark Iron Dwarf' => new CharacterRace(['faction_id' => $alliance_id]),
            'Kul Tiran Human' => new CharacterRace(['faction_id' => $alliance_id]),


            'Orc' => new CharacterRace(['faction_id' => $horde_id]),
            'Undead' => new CharacterRace(['faction_id' => $horde_id]),
            'Tauren' => new CharacterRace(['faction_id' => $horde_id]),
            'Troll' => new CharacterRace(['faction_id' => $horde_id]),
            'Blood Elf' => new CharacterRace(['faction_id' => $horde_id]),
            'Goblin' => new CharacterRace(['faction_id' => $horde_id]),
            'PandarenHorde' => new CharacterRace(['faction_id' => $horde_id]),
            'Nightborne' => new CharacterRace(['faction_id' => $horde_id]),
            'Highmountain Tauren' => new CharacterRace(['faction_id' => $horde_id]),
            'Mag\'har Orc' => new CharacterRace(['faction_id' => $horde_id]),
            'Zandalari Troll' => new CharacterRace(['faction_id' => $horde_id]),
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
            $icon->path = sprintf('images/classes/%s.png', $iconName);
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
            'Goblin' =>                 ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' ', ' ', ' '],
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
