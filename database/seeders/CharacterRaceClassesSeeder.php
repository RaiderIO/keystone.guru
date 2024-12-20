<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CharacterRaceClassCoupling;
use App\Models\Faction;
use App\Models\File;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CharacterRaceClassesSeeder extends Seeder implements TableSeederInterface
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
        /** @var Collection<CharacterRace> $characterRaces */
        $characterRaces = CharacterRace::all()->keyBy('key');
        // Set icons for each inserted class
        /** @var Collection<CharacterClass> $characterClasses */
        $characterClasses = CharacterClass::all()->keyBy('key');

        if ($characterRaces->count() === 0 || $characterClasses->count() === 0) {
            throw new Exception('Unable to find races and classes');
        }

        // In order of the way $classes is structured
        // @formatter:off
        $raceClassMatrix = [
            CharacterRace::CHARACTER_RACE_HUMAN               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_DWARF               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_NIGHT_ELF           => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', 'x', 'x', ' '],
            CharacterRace::CHARACTER_RACE_GNOME               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_DRAENEI             => ['x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', 'x', 'x', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_WORGEN              => ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', ' ', ' '],
            CharacterRace::CHARACTER_RACE_VOID_ELF            => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_LIGHTFORGED_DRAENEI => ['x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', 'x', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_DARK_IRON_DWARF     => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' '],

            CharacterRace::CHARACTER_RACE_PANDAREN_ALLIANCE   => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_PANDAREN_HORDE      => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' ', ' '],

            CharacterRace::CHARACTER_RACE_DRACTHYR_ALLIANCE   => [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', 'x'],
            CharacterRace::CHARACTER_RACE_DRACTHYR_HORDE      => [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', 'x'],

            CharacterRace::CHARACTER_RACE_EARTHEN_ALLIANCE    => ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_EARTHEN_HORDE       => ['x', 'x', ' ', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' '],

            CharacterRace::CHARACTER_RACE_ORC                 => ['x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_UNDEAD              => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_TAUREN              => ['x', 'x', 'x', ' ', 'x', 'x', ' ', ' ', 'x', 'x', 'x', ' ', ' '],
            CharacterRace::CHARACTER_RACE_TROLL               => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' '],
            CharacterRace::CHARACTER_RACE_BLOOD_ELF           => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x', ' '],
            CharacterRace::CHARACTER_RACE_GOBLIN              => ['x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_NIGHTBORNE          => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_HIGHMOUNTAIN_TAUREN => ['x', 'x', 'x', ' ', ' ', 'x', ' ', ' ', 'x', ' ', 'x', ' ', ' '],
            CharacterRace::CHARACTER_RACE_MAGHAR_ORC          => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', ' ', ' ', ' '],

            CharacterRace::CHARACTER_RACE_KUL_TIRAN_HUMAN     => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', ' ', 'x', ' ', ' '],
            CharacterRace::CHARACTER_RACE_ZANDALARI_TROLL     => ['x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', 'x', 'x', 'x', ' ', ' '],

            CharacterRace::CHARACTER_RACE_MECHAGNOME          => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' ', ' '],
            CharacterRace::CHARACTER_RACE_VULPERA             => ['x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', ' ', ' ', ' ', ' '],
        ];

        // @formatter:on

        $raceClassCouplingAttributes = [];
        foreach ($raceClassMatrix as $raceKey => $raceClasses) {
            /** @var CharacterRace $race */
            $race = $characterRaces->get($raceKey);
            $i    = -1;
            foreach ($raceClasses as $raceClass) {
                $i++;

                if ($raceClass !== 'x') {
                    continue;
                }

                $keys = $characterClasses->keys()->toArray();
                /** @var CharacterClass $class */
                $class = $characterClasses[$keys[$i]];

                $raceClassCouplingAttributes[] = [
                    'character_race_id'  => $race->id,
                    'character_class_id' => $class->id,
                ];
            }
        }

        CharacterRaceClassCoupling::from(DatabaseSeeder::getTempTableName(CharacterRaceClassCoupling::class))->insert($raceClassCouplingAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            CharacterRaceClassCoupling::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
