<?php

namespace Database\Seeders;

use App\Models\CharacterRace;
use App\Models\Faction;
use Exception;
use Illuminate\Database\Seeder;

class CharacterRacesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * @throws Exception
     */
    public function run(): void
    {
        $factionAllianceId = Faction::ALL[Faction::FACTION_ALLIANCE];
        $factionHordeId    = Faction::ALL[Faction::FACTION_HORDE];

        if ($factionAllianceId === 0 || $factionHordeId === 0) {
            throw new Exception('Unable to find factions');
        }

        $characterRacesAttributes = [
            CharacterRace::CHARACTER_RACE_HUMAN               => [
                'race_id'    => 1,
                'name'       => 'races.human',
                'key'        => 'human',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_DWARF               => [
                'race_id'    => 3,
                'name'       => 'races.dwarf',
                'key'        => 'dwarf',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_NIGHT_ELF           => [
                'race_id'    => 4,
                'name'       => 'races.night_elf',
                'key'        => 'night_elf',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_GNOME               => [
                'race_id'    => 7,
                'name'       => 'races.gnome',
                'key'        => 'gnome',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_DRAENEI             => [
                'race_id'    => 11,
                'name'       => 'races.draenei',
                'key'        => 'draenei',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_WORGEN              => [
                'race_id'    => 22,
                'name'       => 'races.worgen',
                'key'        => 'worgen',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_PANDAREN_ALLIANCE   => [
                'race_id'    => 24,
                'name'       => 'races.pandarenalliance',
                'key'        => 'pandarenalliance',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_VOID_ELF            => [
                'race_id'    => 29,
                'name'       => 'races.void_elf',
                'key'        => 'void_elf',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_LIGHTFORGED_DRAENEI => [
                'race_id'    => 30,
                'name'       => 'races.lightforged_draenei',
                'key'        => 'lightforged_draenei',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_DARK_IRON_DWARF     => [
                'race_id'    => 34,
                'name'       => 'races.dark_iron_dwarf',
                'key'        => 'dark_iron_dwarf',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_DRACTHYR_ALLIANCE   => [
                'race_id'    => 70,
                'name'       => 'races.dracthyralliance',
                'key'        => 'dracthyralliance',
                'faction_id' => $factionAllianceId,
            ],

            CharacterRace::CHARACTER_RACE_ORC                 => [
                'race_id'    => 2,
                'name'       => 'races.orc',
                'key'        => 'orc',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_UNDEAD              => [
                'race_id'    => 5,
                'name'       => 'races.undead',
                'key'        => 'undead',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_TAUREN              => [
                'race_id'    => 6,
                'name'       => 'races.tauren',
                'key'        => 'tauren',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_TROLL               => [
                'race_id'    => 8,
                'name'       => 'races.troll',
                'key'        => 'troll',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_BLOOD_ELF           => [
                'race_id'    => 10,
                'name'       => 'races.blood_elf',
                'key'        => 'blood_elf',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_GOBLIN              => [
                'race_id'    => 9,
                'name'       => 'races.goblin',
                'key'        => 'goblin',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_PANDAREN_HORDE      => [
                'race_id'    => 24,
                'name'       => 'races.pandarenhorde',
                'key'        => 'pandarenhorde',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_NIGHTBORNE          => [
                'race_id'    => 27,
                'name'       => 'races.nightborne',
                'key'        => 'nightborne',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_HIGHMOUNTAIN_TAUREN => [
                'race_id'    => 28,
                'name'       => 'races.highmountain_tauren',
                'key'        => 'highmountain_tauren',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_MAGHAR_ORC          => [
                'race_id'    => 36,
                'name'       => 'races.maghar_orc',
                'key'        => 'maghar_orc',
                'faction_id' => $factionHordeId,
            ],
            CharacterRace::CHARACTER_RACE_DRACTHYR_HORDE      => [
                'race_id'    => 52,
                'name'       => 'races.dracthyrhorde',
                'key'        => 'dracthyrhorde',
                'faction_id' => $factionHordeId,
            ],

            CharacterRace::CHARACTER_RACE_KUL_TIRAN_HUMAN => [
                'race_id'    => 32,
                'name'       => 'races.kul_tiran_human',
                'key'        => 'kul_tiran_human',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_ZANDALARI_TROLL => [
                'race_id'    => 31,
                'name'       => 'races.zandalari_troll',
                'key'        => 'zandalari_troll',
                'faction_id' => $factionHordeId,
            ],

            CharacterRace::CHARACTER_RACE_MECHAGNOME => [
                'race_id'    => 37,
                'name'       => 'races.mechagnome',
                'key'        => 'mechagnome',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_VULPERA    => [
                'race_id'    => 35,
                'name'       => 'races.vulpera',
                'key'        => 'vulpera',
                'faction_id' => $factionHordeId,
            ],

            CharacterRace::CHARACTER_RACE_EARTHEN_ALLIANCE => [
                'race_id'    => 85,
                'name'       => 'races.earthenalliance',
                'key'        => 'earthenalliance',
                'faction_id' => $factionAllianceId,
            ],
            CharacterRace::CHARACTER_RACE_EARTHEN_HORDE    => [
                'race_id'    => 84,
                'name'       => 'races.earthenhorde',
                'key'        => 'earthenhorde',
                'faction_id' => $factionHordeId,
            ],
        ];

        // Re-index the array and insert the values
        CharacterRace::from(DatabaseSeeder::getTempTableName(CharacterRace::class))->insert(array_values($characterRacesAttributes));
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            CharacterRace::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
