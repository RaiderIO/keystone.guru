<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use App\Models\Faction;
use App\Models\File;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CharacterClassesSeeder extends Seeder implements TableSeederInterface
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

        $characterClassesAttributes = [
            [
                'class_id'     => 1,
                'key'          => CharacterClass::CHARACTER_CLASS_WARRIOR,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_WARRIOR,
                'color'        => '#C79C6E',
                'icon_file_id' => -1,
                // Temporary value
            ],
            [
                'class_id'     => 3,
                'key'          => CharacterClass::CHARACTER_CLASS_HUNTER,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_HUNTER,
                'color'        => '#ABD473',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 6,
                'key'          => CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT,
                'color'        => '#C41F3B',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 8,
                'key'          => CharacterClass::CHARACTER_CLASS_MAGE,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_MAGE,
                'color'        => '#69CCF0',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 5,
                'key'          => CharacterClass::CHARACTER_CLASS_PRIEST,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_PRIEST,
                'color'        => '#FFFFFF',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 10,
                'key'          => CharacterClass::CHARACTER_CLASS_MONK,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_MONK,
                'color'        => '#00FF96',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 4,
                'key'          => CharacterClass::CHARACTER_CLASS_ROGUE,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_ROGUE,
                'color'        => '#FFF569',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 9,
                'key'          => CharacterClass::CHARACTER_CLASS_WARLOCK,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_WARLOCK,
                'color'        => '#9482C9',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 7,
                'key'          => CharacterClass::CHARACTER_CLASS_SHAMAN,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_SHAMAN,
                'color'        => '#0070DE',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 2,
                'key'          => CharacterClass::CHARACTER_CLASS_PALADIN,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_PALADIN,
                'color'        => '#F58CBA',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 11,
                'key'          => CharacterClass::CHARACTER_CLASS_DRUID,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_DRUID,
                'color'        => '#FF7D0A',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 12,
                'key'          => CharacterClass::CHARACTER_CLASS_DEMON_HUNTER,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_DEMON_HUNTER,
                'color'        => '#A330C9',
                'icon_file_id' => -1,
            ],
            [
                'class_id'     => 13,
                'key'          => CharacterClass::CHARACTER_CLASS_EVOKER,
                'name'         => 'classes.' . CharacterClass::CHARACTER_CLASS_EVOKER,
                'color'        => '#33937F',
                'icon_file_id' => -1,
            ],
        ];

        // Insert all classes at once
        CharacterClass::from(DatabaseSeeder::getTempTableName(CharacterClass::class))->insert($characterClassesAttributes);

        // Set icons for each inserted class
        /** @var Collection<CharacterClass> $characterClasses */
        $characterClasses = CharacterClass::from(DatabaseSeeder::getTempTableName(CharacterClass::class))->get();
        foreach ($characterClasses as $characterClass) {
            $icon = File::create([
                'model_id'    => $characterClass->id,
                'model_class' => CharacterClass::class,
                'disk'        => 'public',
                'path'        => sprintf('images/classes/%s.png', strtolower(str_replace(' ', '', $characterClass->key))),
            ]);

            // Update the class with the icon ID
            $characterClass->setTable(DatabaseSeeder::getTempTableName(CharacterClass::class))->update(['icon_file_id' => $icon->id]);
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            CharacterClass::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
