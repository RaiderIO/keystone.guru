<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CharacterRaceClassCoupling;
use App\Models\Faction;
use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CharacterInfoSeeder extends Seeder
{
    /**
     * @throws \Exception
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known races');

        $factionAllianceId = Faction::where('key', Faction::FACTION_ALLIANCE)->first()->id;
        $factionHordeId    = Faction::where('key', Faction::FACTION_HORDE)->first()->id;

        if ($factionAllianceId === 0 || $factionHordeId === 0) {
            throw new \Exception('Unable to find factions');
        }

        // Do the name as key => value so we can easily fetch it later on
        $races = ['Human'               => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Dwarf'               => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Night Elf'           => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Gnome'               => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Draenei'             => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Worgen'              => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'PandarenAlliance'    => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Void Elf'            => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Lightforged Draenei' => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Dark Iron Dwarf'     => new CharacterRace(['faction_id' => $factionAllianceId]),


                  'Orc'                 => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Undead'              => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Tauren'              => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Troll'               => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Blood Elf'           => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Goblin'              => new CharacterRace(['faction_id' => $factionHordeId]),
                  'PandarenHorde'       => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Nightborne'          => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Highmountain Tauren' => new CharacterRace(['faction_id' => $factionHordeId]),
                  'Mag\'har Orc'        => new CharacterRace(['faction_id' => $factionHordeId]),

                  'Kul Tiran Human' => new CharacterRace(['faction_id' => $factionAllianceId]),
                  'Zandalari Troll' => new CharacterRace(['faction_id' => $factionHordeId]),
        ];

        foreach ($races as $name => $race) {
            $race->name = $name;
            // Pesky Pandaren
            $race->name = str_replace('Alliance', '', $race->name);
            $race->name = str_replace('Horde', '', $race->name);
            /** @var $race Model */
            $race->save();
        }

        $this->command->info('Adding known classes');

        // Do NOT change the order of this array!
        $classes = ['Warrior'      => new CharacterClass(['color' => '#C79C6E']),
                    'Hunter'       => new CharacterClass(['color' => '#ABD473']),
                    'Death Knight' => new CharacterClass(['color' => '#C41F3B']),
                    'Mage'         => new CharacterClass(['color' => '#69CCF0']),
                    'Priest'       => new CharacterClass(['color' => '#FFFFFF']),
                    'Monk'         => new CharacterClass(['color' => '#00FF96']),
                    'Rogue'        => new CharacterClass(['color' => '#FFF569']),
                    'Warlock'      => new CharacterClass(['color' => '#9482C9']),
                    'Shaman'       => new CharacterClass(['color' => '#0070DE']),
                    'Paladin'      => new CharacterClass(['color' => '#F58CBA']),
                    'Druid'        => new CharacterClass(['color' => '#FF7D0A']),
                    'Demon Hunter' => new CharacterClass(['color' => '#A330C9'])];

        foreach ($classes as $name => $class) {
            $class->name = $name;
            // Temp file
            $class->icon_file_id = -1;
            /** @var $race Model */
            $class->save();

            $iconName          = strtolower(str_replace(' ', '', $class->name));
            $icon              = new File();
            $icon->model_id    = $class->id;
            $icon->model_class = get_class($class);
            $icon->disk        = 'public';
            $icon->path        = sprintf('images/classes/%s.png', $iconName);
            $icon->save();

            $class->icon_file_id = $icon->id;
            $class->save();
        }

        $this->command->info('Adding known race/class combinations');
        // In order of the way $classes is structured
        // @formatter:off
        $raceClassMatrix = [
            'Human'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' '],
            'Dwarf'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' '],
            'Night Elf'           => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', 'x', 'x'],
            'Gnome'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Draenei'             => ['x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', 'x', 'x', ' ', ' '],
            'Worgen'              => ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', ' '],
            'Void Elf'            => ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Lightforged Draenei' => ['x', 'x', ' ', 'x', 'x', ' ', ' ', ' ', ' ', 'x', ' ', ' '],
            'Dark Iron Dwarf'     => ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' '],

            'PandarenAlliance' => ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],
            'PandarenHorde'    => ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],

            'Orc'                 => ['x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', ' ', ' '],
            'Undead'              => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Tauren'              => ['x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', 'x', 'x', ' '],
            'Troll'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' '],
            'Blood Elf'           => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x'],
            'Goblin'              => ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' ', ' ', ' '],
            'Nightborne'          => ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'Highmountain Tauren' => ['x', 'x', ' ', ' ', ' ', 'x', ' ', ' ', 'x', ' ', 'x', ' '],
            'Mag\'har Orc'        => ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],

            'Kul Tiran Human' => ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x', ' '],
            'Zandalari Troll' => ['x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' '],
        ];
        // @formatter:on

        foreach ($raceClassMatrix as $raceStr => $raceClasses) {
            $race = $races[$raceStr];
            $i    = 0;
            foreach ($raceClasses as $raceClass) {
                if ($raceClass === 'x') {
                    $keys  = array_keys($classes);
                    $class = $classes[$keys[$i]];

                    $raceClassCoupling                     = new CharacterRaceClassCoupling();
                    $raceClassCoupling->character_race_id  = $race->id;
                    $raceClassCoupling->character_class_id = $class->id;

                    $raceClassCoupling->save();
                }
                $i++;
            }
        }


        $this->command->info('Adding known class/specialization combinations');
        // @formatter:off
        $classSpecializationMatrix = [
            'Death Knight' => ['Blood', 'Frost', 'Unholy'],
            'Demon Hunter' => ['Havoc', 'Vengeance'],
            'Druid'        => ['Balance', 'Feral', 'Guardian', 'Restoration'],
            'Hunter'       => ['Beast Mastery', 'Marksman', 'Survival'],
            'Mage'         => ['Arcane', 'Fire', 'Frost'],
            'Monk'         => ['Brewmaster', 'Mistweaver', 'Windwalker'],
            'Paladin'      => ['Holy', 'Protection', 'Retribution'],
            'Priest'       => ['Discipline', 'Holy', 'Shadow'],
            'Rogue'        => ['Assassination', 'Outlaw', 'Subtlety'],
            'Shaman'       => ['Elemental', 'Enhancement', 'Restoration'],
            'Warlock'      => ['Affliction', 'Demonology', 'Destruction'],
            'Warrior'      => ['Arms', 'Fury', 'Protection'],
        ];
        // @formatter:on

        // For each class with a bunch of specs
        foreach ($classSpecializationMatrix as $classStr => $specializations) {
            // Fetch the class
            $class = $classes[$classStr];
            // For each of their specs
            foreach ($specializations as $specialization) {
                $characterClassSpecialization                     = new CharacterClassSpecialization();
                $characterClassSpecialization->character_class_id = $class->id;
                $characterClassSpecialization->name               = $specialization;
                // Dummy file ID
                $characterClassSpecialization->icon_file_id = -1;

                $characterClassSpecialization->save();

                $classKey = strtolower(str_replace(' ', '', $class->name));
                $specKey  = strtolower(str_replace(' ', '', $specialization));

                $icon              = new File();
                $icon->model_id    = $characterClassSpecialization->id;
                $icon->model_class = get_class($characterClassSpecialization);
                $icon->disk        = 'public';
                $icon->path        = sprintf('images/specializations/%s/%s_%s.png', $classKey, $classKey, $specKey);
                $icon->save();

                $characterClassSpecialization->icon_file_id = $icon->id;
                $characterClassSpecialization->save();
            }
        }
    }

    private function _rollback()
    {
        DB::table('character_races')->truncate();
        DB::table('character_classes')->truncate();
        DB::table('character_class_specializations')->truncate();
        DB::table('character_race_class_couplings')->truncate();
        DB::table('files')->where('model_class', 'App\Models\CharacterClass')->delete();
        DB::table('files')->where('model_class', 'App\Models\CharacterClassSpecialization')->delete();
    }
}
