<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\File;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CharacterClassSpecializationsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * @throws Exception
     */
    public function run(): void
    {
        /** @var Collection<CharacterClass> $characterClasses */
        $characterClasses = CharacterClass::all()->keyBy('key');

        // @formatter:off
        $characterClassSpecializationsAttributes = [
            [
                'key'                => 'blood',
                'name'               => 'specializations.death_knight.blood',
                'specialization_id'  => 250,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT)->id,
            ],
            [
                'key'                => 'frost',
                'name'               => 'specializations.death_knight.frost',
                'specialization_id'  => 251,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT)->id,
            ],
            [
                'key'                => 'unholy',
                'name'               => 'specializations.death_knight.unholy',
                'specialization_id'  => 252,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT)->id,
            ],
            [
                'key'                => 'havoc',
                'name'               => 'specializations.demon_hunter.havoc',
                'specialization_id'  => 577,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DEMON_HUNTER)->id,
            ],
            [
                'key'                => 'vengeance',
                'name'               => 'specializations.demon_hunter.vengeance',
                'specialization_id'  => 581,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DEMON_HUNTER)->id,
            ],
            [
                'key'                => 'balance',
                'name'               => 'specializations.druid.balance',
                'specialization_id'  => 102,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DRUID)->id,
            ],
            [
                'key'                => 'feral',
                'name'               => 'specializations.druid.feral',
                'specialization_id'  => 103,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DRUID)->id,
            ],
            [
                'key'                => 'guardian',
                'name'               => 'specializations.druid.guardian',
                'specialization_id'  => 104,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DRUID)->id,
            ],
            [
                'key'                => 'restoration',
                'name'               => 'specializations.druid.restoration',
                'specialization_id'  => 105,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DRUID)->id,
            ],
            [
                'key'                => 'devastation',
                'name'               => 'specializations.evoker.devastation',
                'specialization_id'  => 1467,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_EVOKER)->id,
            ],
            [
                'key'                => 'preservation',
                'name'               => 'specializations.evoker.preservation',
                'specialization_id'  => 1468,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_EVOKER)->id,
            ],
            [
                'key'                => 'augmentation',
                'name'               => 'specializations.evoker.augmentation',
                'specialization_id'  => 1473,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_EVOKER)->id,
            ],
            [
                'key'                => 'beast_mastery',
                'name'               => 'specializations.hunter.beast_mastery',
                'specialization_id'  => 253,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_HUNTER)->id,
            ],
            [
                'key'                => 'marksmanship',
                'name'               => 'specializations.hunter.marksmanship',
                'specialization_id'  => 254,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_HUNTER)->id,
            ],
            [
                'key'                => 'survival',
                'name'               => 'specializations.hunter.survival',
                'specialization_id'  => 255,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_HUNTER)->id,
            ],
            [
                'key'                => 'arcane',
                'name'               => 'specializations.mage.arcane',
                'specialization_id'  => 62,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_MAGE)->id,
            ],
            [
                'key'                => 'fire',
                'name'               => 'specializations.mage.fire',
                'specialization_id'  => 63,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_MAGE)->id,
            ],
            [
                'key'                => 'frost',
                'name'               => 'specializations.mage.frost',
                'specialization_id'  => 64,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_MAGE)->id,
            ],
            [
                'key'                => 'brewmaster',
                'name'               => 'specializations.monk.brewmaster',
                'specialization_id'  => 268,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_MONK)->id,
            ],
            [
                'key'                => 'mistweaver',
                'name'               => 'specializations.monk.mistweaver',
                'specialization_id'  => 270,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_MONK)->id,
            ],
            [
                'key'                => 'windwalker',
                'name'               => 'specializations.monk.windwalker',
                'specialization_id'  => 269,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_MONK)->id,
            ],
            [
                'key'                => 'holy',
                'name'               => 'specializations.paladin.holy',
                'specialization_id'  => 65,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_PALADIN)->id,
            ],
            [
                'key'                => 'protection',
                'name'               => 'specializations.paladin.protection',
                'specialization_id'  => 66,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_PALADIN)->id,
            ],
            [
                'key'                => 'retribution',
                'name'               => 'specializations.paladin.retribution',
                'specialization_id'  => 70,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_PALADIN)->id,
            ],
            [
                'key'                => 'discipline',
                'name'               => 'specializations.priest.discipline',
                'specialization_id'  => 256,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_PRIEST)->id,
            ],
            [
                'key'                => 'holy',
                'name'               => 'specializations.priest.holy',
                'specialization_id'  => 257,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_PRIEST)->id,
            ],
            [
                'key'                => 'shadow',
                'name'               => 'specializations.priest.shadow',
                'specialization_id'  => 258,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_PRIEST)->id,
            ],
            [
                'key'                => 'assassination',
                'name'               => 'specializations.rogue.assassination',
                'specialization_id'  => 259,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_ROGUE)->id,
            ],
            [
                'key'                => 'outlaw',
                'name'               => 'specializations.rogue.outlaw',
                'specialization_id'  => 260,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_ROGUE)->id,
            ],
            [
                'key'                => 'subtlety',
                'name'               => 'specializations.rogue.subtlety',
                'specialization_id'  => 261,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_ROGUE)->id,
            ],
            [
                'key'                => 'elemental',
                'name'               => 'specializations.shaman.elemental',
                'specialization_id'  => 262,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_SHAMAN)->id,
            ],
            [
                'key'                => 'enhancement',
                'name'               => 'specializations.shaman.enhancement',
                'specialization_id'  => 263,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_SHAMAN)->id,
            ],
            [
                'key'                => 'restoration',
                'name'               => 'specializations.shaman.restoration',
                'specialization_id'  => 264,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_SHAMAN)->id,
            ],
            [
                'key'                => 'affliction',
                'name'               => 'specializations.warlock.affliction',
                'specialization_id'  => 265,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_WARLOCK)->id,
            ],
            [
                'key'                => 'demonology',
                'name'               => 'specializations.warlock.demonology',
                'specialization_id'  => 266,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_WARLOCK)->id,
            ],
            [
                'key'                => 'destruction',
                'name'               => 'specializations.warlock.destruction',
                'specialization_id'  => 267,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_WARLOCK)->id,
            ],
            [
                'key'                => 'arms',
                'name'               => 'specializations.warrior.arms',
                'specialization_id'  => 71,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_WARRIOR)->id,
            ],
            [
                'key'                => 'fury',
                'name'               => 'specializations.warrior.fury',
                'specialization_id'  => 72,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_WARRIOR)->id,
            ],
            [
                'key'                => 'protection',
                'name'               => 'specializations.warrior.protection',
                'specialization_id'  => 73,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_WARRIOR)->id,
            ],
            [
                'key'                => 'devourer',
                'name'               => 'specializations.demon_hunter.devourer',
                'specialization_id'  => 1480,
                'character_class_id' => $characterClasses->get(CharacterClass::CHARACTER_CLASS_DEMON_HUNTER)->id,
            ],
        ];

        CharacterClassSpecialization::from(DatabaseSeeder::getTempTableName(CharacterClassSpecialization::class))
            ->insert(collect($characterClassSpecializationsAttributes)->map(function ($row) {
                $row['icon_file_id'] = -1;

                return $row;
            })->toArray());

        $characterClasses              = $characterClasses->keyBy('id');
        $characterClassSpecializations = CharacterClassSpecialization::all();
        // @formatter:on

        // For each class with a bunch of specs
        foreach ($characterClassSpecializations as $characterClassSpecialization) {
            /** @var CharacterClass $class */
            $class = $characterClasses->get($characterClassSpecialization->character_class_id);

            $icon = File::create([
                'model_id'    => $characterClassSpecialization->id,
                'model_class' => get_class($characterClassSpecialization),
                'disk'        => 'public',
                'path'        => sprintf('images/specializations/%s/%s_%s.png', $class->key, $class->key, $characterClassSpecialization->key),
            ]);

            $characterClassSpecialization->setTable(DatabaseSeeder::getTempTableName(CharacterClassSpecialization::class))->update([
                'icon_file_id' => $icon->id,
            ]);
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            CharacterClassSpecialization::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
