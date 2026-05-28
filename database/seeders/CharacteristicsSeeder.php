<?php

namespace Database\Seeders;

use App\Models\Characteristic;
use Illuminate\Database\Seeder;

class CharacteristicsSeeder extends Seeder implements TableSeederInterface
{
    private const array ICON_NAMES = [
        Characteristic::CHARACTERISTIC_TAUNT           => 'spell_nature_reincarnation',
        Characteristic::CHARACTERISTIC_INCAPACITATE    => 'ability_gouge',
        Characteristic::CHARACTERISTIC_SUBJUGATE_DEMON => 'spell_shadow_enslavedemon',
        Characteristic::CHARACTERISTIC_CONTROL_UNDEAD  => 'inv_misc_bone_skull_01',
        Characteristic::CHARACTERISTIC_SILENCE         => 'ability_priest_silence',
        Characteristic::CHARACTERISTIC_KNOCK           => 'ability_druid_typhoon',
        Characteristic::CHARACTERISTIC_GRIP            => 'spell_deathknight_strangulate',
        Characteristic::CHARACTERISTIC_SHACKLE_UNDEAD  => 'spell_nature_slow',
        Characteristic::CHARACTERISTIC_MIND_CONTROL    => 'spell_shadow_shadowworddominate',
        Characteristic::CHARACTERISTIC_POLYMORPH       => 'spell_nature_polymorph',
        Characteristic::CHARACTERISTIC_ROOT            => 'spell_nature_stranglevines',
        Characteristic::CHARACTERISTIC_FEAR            => 'spell_shadow_possession',
        Characteristic::CHARACTERISTIC_BANISH          => 'spell_shadow_cripple',
        Characteristic::CHARACTERISTIC_DISORIENT       => 'ability_golemstormbolt',
        Characteristic::CHARACTERISTIC_REPENTANCE      => 'spell_holy_prayerofhealing',
        Characteristic::CHARACTERISTIC_IMPRISON        => 'ability_demonhunter_imprison',
        Characteristic::CHARACTERISTIC_SAP             => 'ability_sap',
        Characteristic::CHARACTERISTIC_STUN            => 'spell_frost_stun',
        Characteristic::CHARACTERISTIC_SLOW            => 'spell_nature_slow',
        Characteristic::CHARACTERISTIC_SLEEP_WALK      => 'ability_xavius_dreamsimulacrum',
        Characteristic::CHARACTERISTIC_SCARE_BEAST     => 'ability_druid_cower',
        Characteristic::CHARACTERISTIC_HIBERNATE       => 'spell_nature_sleep',
        Characteristic::CHARACTERISTIC_TURN_EVIL       => 'ability_paladin_turnevil',
        Characteristic::CHARACTERISTIC_MIND_SOOTHE     => 'spell_holy_mindsooth',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $characteristicsAttributes = [];

        foreach (Characteristic::ALL as $key => $id) {
            $characteristicsAttributes[] = [
                'id'        => $id,
                'name'      => sprintf('characteristics.%s', $key),
                'key'       => $key,
                'icon_name' => self::ICON_NAMES[$key],
            ];
        }

        Characteristic::from(DatabaseSeeder::getTempTableName(Characteristic::class))->insert($characteristicsAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [Characteristic::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
