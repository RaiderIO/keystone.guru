<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CharacterRaceClassCoupling;
use App\Models\Faction;
use App\Models\File;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CharacterInfoSeeder extends Seeder
{
    /**
     * @throws Exception
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known races');

        $factionAllianceId = Faction::ALL[Faction::FACTION_ALLIANCE];
        $factionHordeId    = Faction::ALL[Faction::FACTION_HORDE];

        if ($factionAllianceId === 0 || $factionHordeId === 0) {
            throw new Exception('Unable to find factions');
        }

        // Do the name as key => value so we can easily fetch it later on
        $races = [
            'races.human'               => new CharacterRace(['key' => 'human', 'faction_id' => $factionAllianceId]),
            'races.dwarf'               => new CharacterRace(['key' => 'dwarf', 'faction_id' => $factionAllianceId]),
            'races.night_elf'           => new CharacterRace(['key' => 'night_elf', 'faction_id' => $factionAllianceId]),
            'races.gnome'               => new CharacterRace(['key' => 'gnome', 'faction_id' => $factionAllianceId]),
            'races.draenei'             => new CharacterRace(['key' => 'draenei', 'faction_id' => $factionAllianceId]),
            'races.worgen'              => new CharacterRace(['key' => 'worgen', 'faction_id' => $factionAllianceId]),
            'races.pandarenalliance'    => new CharacterRace(['key' => 'pandarenalliance', 'faction_id' => $factionAllianceId]),
            'races.void_elf'            => new CharacterRace(['key' => 'void_elf', 'faction_id' => $factionAllianceId]),
            'races.lightforged_draenei' => new CharacterRace(['key' => 'lightforged_draenei', 'faction_id' => $factionAllianceId]),
            'races.dark_iron_dwarf'     => new CharacterRace(['key' => 'dark_iron_dwarf', 'faction_id' => $factionAllianceId]),

            'races.orc'                 => new CharacterRace(['key' => 'orc', 'faction_id' => $factionHordeId]),
            'races.undead'              => new CharacterRace(['key' => 'undead', 'faction_id' => $factionHordeId]),
            'races.tauren'              => new CharacterRace(['key' => 'tauren', 'faction_id' => $factionHordeId]),
            'races.troll'               => new CharacterRace(['key' => 'troll', 'faction_id' => $factionHordeId]),
            'races.blood_elf'           => new CharacterRace(['key' => 'blood_elf', 'faction_id' => $factionHordeId]),
            'races.goblin'              => new CharacterRace(['key' => 'goblin', 'faction_id' => $factionHordeId]),
            'races.pandarenhorde'       => new CharacterRace(['key' => 'pandarenhorde', 'faction_id' => $factionHordeId]),
            'races.nightborne'          => new CharacterRace(['key' => 'nightborne', 'faction_id' => $factionHordeId]),
            'races.highmountain_tauren' => new CharacterRace(['key' => 'highmountain_tauren', 'faction_id' => $factionHordeId]),
            'races.maghar_orc'          => new CharacterRace(['key' => 'maghar_orc', 'faction_id' => $factionHordeId]),

            'races.kul_tiran_human' => new CharacterRace(['key' => 'kul_tiran_human', 'faction_id' => $factionAllianceId]),
            'races.zandalari_troll' => new CharacterRace(['key' => 'zandalari_troll', 'faction_id' => $factionHordeId]),

            'races.mechagnome' => new CharacterRace(['key' => 'mechagnome', 'faction_id' => $factionAllianceId]),
            'races.vulpera'    => new CharacterRace(['key' => 'vulpera', 'faction_id' => $factionHordeId]),
        ];

        foreach ($races as $name => $race) {
            $race->name = $name;
            $race->save();
        }

        $this->command->info('Adding known classes');

        // Do NOT change the order of this array!
        $classes = [
            'classes.warrior'      => new CharacterClass(['key' => 'warrior', 'color' => '#C79C6E']),
            'classes.hunter'       => new CharacterClass(['key' => 'hunter', 'color' => '#ABD473']),
            'classes.death_knight' => new CharacterClass(['key' => 'deathknight', 'color' => '#C41F3B']),
            'classes.mage'         => new CharacterClass(['key' => 'mage', 'color' => '#69CCF0']),
            'classes.priest'       => new CharacterClass(['key' => 'priest', 'color' => '#FFFFFF']),
            'classes.monk'         => new CharacterClass(['key' => 'monk', 'color' => '#00FF96']),
            'classes.rogue'        => new CharacterClass(['key' => 'rogue', 'color' => '#FFF569']),
            'classes.warlock'      => new CharacterClass(['key' => 'warlock', 'color' => '#9482C9']),
            'classes.shaman'       => new CharacterClass(['key' => 'shaman', 'color' => '#0070DE']),
            'classes.paladin'      => new CharacterClass(['key' => 'paladin', 'color' => '#F58CBA']),
            'classes.druid'        => new CharacterClass(['key' => 'druid', 'color' => '#FF7D0A']),
            'classes.demon_hunter' => new CharacterClass(['key' => 'demonhunter', 'color' => '#A330C9']),
        ];

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
            'races.human'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' '],
            'races.dwarf'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' '],
            'races.night_elf'           => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', 'x', 'x'],
            'races.gnome'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'races.draenei'             => ['x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', 'x', 'x', ' ', ' '],
            'races.worgen'              => ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', ' '],
            'races.void_elf'            => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'races.lightforged_draenei' => ['x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', 'x', ' ', ' '],
            'races.dark_iron_dwarf'      => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' '],

            'races.pandarenalliance' => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],
            'races.pandarenhorde'    => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],

            'races.orc'                 => ['x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', ' ', ' '],
            'races.undead'              => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'races.tauren'              => ['x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', 'x', 'x', ' '],
            'races.troll'               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' '],
            'races.blood_elf'           => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x'],
            'races.goblin'              => ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' ', ' ', ' '],
            'races.nightborne'          => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'races.highmountain_tauren' => ['x', 'x', 'x', ' ', ' ', 'x', ' ', ' ', 'x', ' ', 'x', ' '],
            'races.maghar_orc'          => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],

            'races.kul_tiran_human' => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x', ' '],
            'races.zandalari_troll' => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' '],

            'races.mechagnome' => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            'races.vulpera'    => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' '],
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
            'classes.death_knight' => [
                new CharacterClassSpecialization(['key' => 'blood', 'name' => 'specializations.death_knight.blood']),
                new CharacterClassSpecialization(['key' => 'frost', 'name' => 'specializations.death_knight.frost']),
                new CharacterClassSpecialization(['key' => 'unholy', 'name' => 'specializations.death_knight.unholy']),
            ],
            'classes.demon_hunter' => [
                new CharacterClassSpecialization(['key' => 'havoc', 'name' => 'specializations.demon_hunter.havoc']),
                new CharacterClassSpecialization(['key' => 'vengeance', 'name' => 'specializations.demon_hunter.vengeance']),
            ],
            'classes.druid'        => [
                new CharacterClassSpecialization(['key' => 'balance', 'name' => 'specializations.druid.balance']),
                new CharacterClassSpecialization(['key' => 'feral', 'name' => 'specializations.druid.feral']),
                new CharacterClassSpecialization(['key' => 'guardian', 'name' => 'specializations.druid.guardian']),
                new CharacterClassSpecialization(['key' => 'restoration', 'name' => 'specializations.druid.restoration']),
            ],
            'classes.hunter'       => [
                new CharacterClassSpecialization(['key' => 'beast_mastery', 'name' => 'specializations.hunter.beast_mastery']),
                new CharacterClassSpecialization(['key' => 'marksman', 'name' => 'specializations.hunter.marksman']),
                new CharacterClassSpecialization(['key' => 'survival', 'name' => 'specializations.hunter.survival']),
            ],
            'classes.mage'         => [
                new CharacterClassSpecialization(['key' => 'arcane', 'name' => 'specializations.mage.arcane']),
                new CharacterClassSpecialization(['key' => 'fire', 'name' => 'specializations.mage.fire']),
                new CharacterClassSpecialization(['key' => 'frost', 'name' => 'specializations.mage.frost']),
            ],
            'classes.monk'         => [
                new CharacterClassSpecialization(['key' => 'brewmaster', 'name' => 'specializations.monk.brewmaster']),
                new CharacterClassSpecialization(['key' => 'mistweaver', 'name' => 'specializations.monk.mistweaver']),
                new CharacterClassSpecialization(['key' => 'windwalker', 'name' => 'specializations.monk.windwalker']),
            ],
            'classes.paladin'      => [
                new CharacterClassSpecialization(['key' => 'holy', 'name' => 'specializations.paladin.holy']),
                new CharacterClassSpecialization(['key' => 'protection', 'name' => 'specializations.paladin.protection']),
                new CharacterClassSpecialization(['key' => 'retribution', 'name' => 'specializations.paladin.retribution']),
            ],
            'classes.priest'       => [
                new CharacterClassSpecialization(['key' => 'discipline', 'name' => 'specializations.priest.discipline']),
                new CharacterClassSpecialization(['key' => 'holy', 'name' => 'specializations.priest.holy']),
                new CharacterClassSpecialization(['key' => 'shadow', 'name' => 'specializations.priest.shadow']),
            ],
            'classes.rogue'        => [
                new CharacterClassSpecialization(['key' => 'assassination', 'name' => 'specializations.rogue.assassination']),
                new CharacterClassSpecialization(['key' => 'outlaw', 'name' => 'specializations.rogue.outlaw']),
                new CharacterClassSpecialization(['key' => 'subtlety', 'name' => 'specializations.rogue.subtlety']),
            ],
            'classes.shaman'       => [
                new CharacterClassSpecialization(['key' => 'elemental', 'name' => 'specializations.shaman.elemental']),
                new CharacterClassSpecialization(['key' => 'enhancement', 'name' => 'specializations.shaman.enhancement']),
                new CharacterClassSpecialization(['key' => 'restoration', 'name' => 'specializations.shaman.restoration']),
            ],
            'classes.warlock'      => [
                new CharacterClassSpecialization(['key' => 'affliction', 'name' => 'specializations.warlock.affliction']),
                new CharacterClassSpecialization(['key' => 'demonology', 'name' => 'specializations.warlock.demonology']),
                new CharacterClassSpecialization(['key' => 'destruction', 'name' => 'specializations.warlock.destruction']),
            ],
            'classes.warrior'      => [
                new CharacterClassSpecialization(['key' => 'arms', 'name' => 'specializations.warrior.arms']),
                new CharacterClassSpecialization(['key' => 'fury', 'name' => 'specializations.warrior.fury']),
                new CharacterClassSpecialization(['key' => 'protection', 'name' => 'specializations.warrior.protection']),
            ],
        ];
        // @formatter:on

        // For each class with a bunch of specs
        foreach ($classSpecializationMatrix as $classStr => $specializations) {
            // Fetch the class
            $class = $classes[$classStr];
            // For each of their specs
            foreach ($specializations as $specialization) {
                $specialization->character_class_id = $class->id;
                // Dummy file ID
                $specialization->icon_file_id = -1;
                $specialization->save();

                $icon              = new File();
                $icon->model_id    = $specialization->id;
                $icon->model_class = get_class($specialization);
                $icon->disk        = 'public';
                $icon->path        = sprintf('images/specializations/%s/%s_%s.png', $class->key, $class->key, $specialization->key);
                $icon->save();

                $specialization->icon_file_id = $icon->id;
                $specialization->save();
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
