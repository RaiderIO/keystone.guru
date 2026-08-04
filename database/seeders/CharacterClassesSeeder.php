<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use App\Models\Faction;
use Illuminate\Database\Seeder;

class CharacterClassesSeeder extends Seeder implements TableSeederInterface
{
    public function run(): void
    {
        $factionAllianceId = Faction::ALL[Faction::FACTION_ALLIANCE];
        $factionHordeId    = Faction::ALL[Faction::FACTION_HORDE];

        $characterClassesAttributes = [
            [
                'class_id' => 1,
                'key'      => CharacterClass::CHARACTER_CLASS_WARRIOR,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_WARRIOR,
                'color'    => '#C79C6E',
            ],
            [
                'class_id' => 3,
                'key'      => CharacterClass::CHARACTER_CLASS_HUNTER,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_HUNTER,
                'color'    => '#ABD473',
            ],
            [
                'class_id' => 6,
                'key'      => CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT,
                'color'    => '#C41F3B',
            ],
            [
                'class_id' => 8,
                'key'      => CharacterClass::CHARACTER_CLASS_MAGE,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_MAGE,
                'color'    => '#69CCF0',
            ],
            [
                'class_id' => 5,
                'key'      => CharacterClass::CHARACTER_CLASS_PRIEST,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_PRIEST,
                'color'    => '#FFFFFF',
            ],
            [
                'class_id' => 10,
                'key'      => CharacterClass::CHARACTER_CLASS_MONK,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_MONK,
                'color'    => '#00FF96',
            ],
            [
                'class_id' => 4,
                'key'      => CharacterClass::CHARACTER_CLASS_ROGUE,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_ROGUE,
                'color'    => '#FFF569',
            ],
            [
                'class_id' => 9,
                'key'      => CharacterClass::CHARACTER_CLASS_WARLOCK,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_WARLOCK,
                'color'    => '#9482C9',
            ],
            [
                'class_id' => 7,
                'key'      => CharacterClass::CHARACTER_CLASS_SHAMAN,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_SHAMAN,
                'color'    => '#0070DE',
            ],
            [
                'class_id' => 2,
                'key'      => CharacterClass::CHARACTER_CLASS_PALADIN,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_PALADIN,
                'color'    => '#F58CBA',
            ],
            [
                'class_id' => 11,
                'key'      => CharacterClass::CHARACTER_CLASS_DRUID,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_DRUID,
                'color'    => '#FF7D0A',
            ],
            [
                'class_id' => 12,
                'key'      => CharacterClass::CHARACTER_CLASS_DEMON_HUNTER,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_DEMON_HUNTER,
                'color'    => '#A330C9',
            ],
            [
                'class_id' => 13,
                'key'      => CharacterClass::CHARACTER_CLASS_EVOKER,
                'name'     => 'classes.' . CharacterClass::CHARACTER_CLASS_EVOKER,
                'color'    => '#33937F',
            ],
        ];

        // Insert all classes at once
        CharacterClass::from(DatabaseSeeder::getTempTableName(CharacterClass::class))->insert($characterClassesAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            CharacterClass::class,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
