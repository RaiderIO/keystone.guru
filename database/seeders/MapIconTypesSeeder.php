<?php

namespace Database\Seeders;

use App\Models\MapIcon;
use App\Models\MapIconType;
use Illuminate\Database\Seeder;

class MapIconTypesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mapIconTypes = [
            MapIconType::MAP_ICON_TYPE_UNKNOWN                   => ['name' => 'mapicontypes.unknown',                   'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_COMMENT                   => ['name' => 'mapicontypes.comment',                  'width' => 24, 'height' => 24],

            MapIconType::MAP_ICON_TYPE_DOOR                      => ['name' => 'mapicontypes.door',                     'width' => 25, 'height' => 22, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_DOWN                 => ['name' => 'mapicontypes.door_down',                'width' => 23, 'height' => 24, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_LEFT                 => ['name' => 'mapicontypes.door_left',                'width' => 23, 'height' => 22, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_LOCKED               => ['name' => 'mapicontypes.door_locked',              'width' => 26, 'height' => 24, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_RIGHT                => ['name' => 'mapicontypes.door_right',               'width' => 23, 'height' => 22, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_UP                   => ['name' => 'mapicontypes.door_up',                  'width' => 23, 'height' => 24, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_DOT_YELLOW                => ['name' => 'mapicontypes.dot_yellow',               'width' => 19, 'height' => 19],
            MapIconType::MAP_ICON_TYPE_DUNGEON_START             => ['name' => 'mapicontypes.dungeon_start',            'width' => 24, 'height' => 24, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_GATEWAY                   => ['name' => 'mapicontypes.gateway',                  'width' => 29, 'height' => 28],

            MapIconType::MAP_ICON_TYPE_GRAVEYARD                 => ['name' => 'mapicontypes.graveyard',                'width' => 14, 'height' => 18, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_GREASEBOT                 => ['name' => 'mapicontypes.greasebot',                'width' => 20, 'height' => 19, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_SHOCKBOT                  => ['name' => 'mapicontypes.shockbot',                 'width' => 16, 'height' => 17, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_WARLOCK_GATEWAY           => ['name' => 'mapicontypes.warlock_gateway',          'width' => 35, 'height' => 34],
            MapIconType::MAP_ICON_TYPE_WELDINGBOT                => ['name' => 'mapicontypes.weldingbot',               'width' => 16, 'height' => 19, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL   => ['name' => 'mapicontypes.awakened_obelisk_brutal',   'width' => 25, 'height' => 37, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED   => ['name' => 'mapicontypes.awakened_obelisk_cursed',   'width' => 25, 'height' => 37, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED  => ['name' => 'mapicontypes.awakened_obelisk_defiled',  'width' => 25, 'height' => 37, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC => ['name' => 'mapicontypes.awakened_obelisk_entropic', 'width' => 25, 'height' => 37, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_SKIP_FLIGHT   => ['name' => 'mapicontypes.skip_flight',   'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_SKIP_TELEPORT => ['name' => 'mapicontypes.skip_teleport', 'width' => 25, 'height' => 25, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_SKIP_WALK     => ['name' => 'mapicontypes.skip_walk',     'width' => 26, 'height' => 34, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_RAID_MARKER_STAR     => ['name' => 'mapicontypes.raid_marker_star',     'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_CIRCLE   => ['name' => 'mapicontypes.raid_marker_circle',   'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_DIAMOND  => ['name' => 'mapicontypes.raid_marker_diamond',  'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_TRIANGLE => ['name' => 'mapicontypes.raid_marker_triangle', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_MOON     => ['name' => 'mapicontypes.raid_marker_moon',     'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_SQUARE   => ['name' => 'mapicontypes.raid_marker_square',   'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_CROSS    => ['name' => 'mapicontypes.raid_marker_cross',    'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_SKULL    => ['name' => 'mapicontypes.raid_marker_skull',    'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_SPELL_BLOODLUST             => ['name' => 'mapicontypes.spell_bloodlust',             'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_SPELL_HEROISM               => ['name' => 'mapicontypes.spell_heroism',               'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_SPELL_SHADOWMELD            => ['name' => 'mapicontypes.spell_shadowmeld',            'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_SPELL_SHROUD_OF_CONCEALMENT => ['name' => 'mapicontypes.spell_shroud_of_concealment', 'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_ITEM_INVISIBILITY                 => ['name' => 'mapicontypes.item_invisibility',                  'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_ITEM_DRUMS_OF_SPEED               => ['name' => 'mapicontypes.item_drums_of_speed',               'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_ITEM_FREE_ACTION_POTION           => ['name' => 'mapicontypes.item_free_action_potion',           'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_ITEM_GLOBAL_THERMAL_SAPPER_CHARGE => ['name' => 'mapicontypes.item_global_thermal_sapper_charge', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_ITEM_ROCKET_BOOTS_XTREME          => ['name' => 'mapicontypes.item_rocket_boots_xtreme',          'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_QUESTION_YELLOW => ['name' => 'mapicontypes.question_yellow', 'width' => 17, 'height' => 28],
            MapIconType::MAP_ICON_TYPE_QUESTION_BLUE   => ['name' => 'mapicontypes.question_blue',   'width' => 17, 'height' => 28],
            MapIconType::MAP_ICON_TYPE_QUESTION_ORANGE => ['name' => 'mapicontypes.question_orange', 'width' => 17, 'height' => 28],

            MapIconType::MAP_ICON_TYPE_EXCLAMATION_YELLOW => ['name' => 'mapicontypes.exclamation_yellow', 'width' => 13, 'height' => 30],
            MapIconType::MAP_ICON_TYPE_EXCLAMATION_BLUE   => ['name' => 'mapicontypes.exclamation_blue',   'width' => 13, 'height' => 30],
            MapIconType::MAP_ICON_TYPE_EXCLAMATION_ORANGE => ['name' => 'mapicontypes.exclamation_orange', 'width' => 13, 'height' => 30],

            MapIconType::MAP_ICON_TYPE_NEONBUTTON_BLUE      => ['name' => 'mapicontypes.neonbutton_blue',      'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_CYAN      => ['name' => 'mapicontypes.neonbutton_cyan',      'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_GREEN     => ['name' => 'mapicontypes.neonbutton_green',     'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_ORANGE    => ['name' => 'mapicontypes.neonbutton_orange',    'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_PINK      => ['name' => 'mapicontypes.neonbutton_pink',      'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_PURPLE    => ['name' => 'mapicontypes.neonbutton_purple',    'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_RED       => ['name' => 'mapicontypes.neonbutton_red',       'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_YELLOW    => ['name' => 'mapicontypes.neonbutton_yellow',    'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_DARKRED   => ['name' => 'mapicontypes.neonbutton_darkred',   'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_DARKGREEN => ['name' => 'mapicontypes.neonbutton_darkgreen', 'width' => 24, 'height' => 24],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_DARKBLUE  => ['name' => 'mapicontypes.neonbutton_darkblue',  'width' => 24, 'height' => 24],

            MapIconType::MAP_ICON_TYPE_SPELL_MIND_SOOTHE => ['name' => 'mapicontypes.spell_mind_soothe', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_SPELL_COMBUSTION  => ['name' => 'mapicontypes.spell_combustion',  'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_COVENANT_KYRIAN     => ['name' => 'mapicontypes.covenant_kyrian', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_COVENANT_NECROLORDS => ['name' => 'mapicontypes.covenant_necrolords', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_COVENANT_NIGHTFAE   => ['name' => 'mapicontypes.covenant_nightfae', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_COVENANT_VENTHYR    => ['name' => 'mapicontypes.covenant_venthyr', 'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_PORTAL_BLUE   => ['name' => 'mapicontypes.portal_blue', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_PORTAL_GREEN  => ['name' => 'mapicontypes.portal_green', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_PORTAL_ORANGE => ['name' => 'mapicontypes.portal_orange', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_PORTAL_PINK   => ['name' => 'mapicontypes.portal_pink', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_PORTAL_RED    => ['name' => 'mapicontypes.portal_red', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_NW_ITEM_ANIMA   => ['name' => 'mapicontypes.nw_item_anima', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_NW_ITEM_GOLIATH => ['name' => 'mapicontypes.nw_item_goliath', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_NW_ITEM_HAMMER  => ['name' => 'mapicontypes.nw_item_hammer', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_NW_ITEM_SHIELD  => ['name' => 'mapicontypes.nw_item_shield', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_NW_ITEM_SPEAR   => ['name' => 'mapicontypes.nw_item_spear', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_SPELL_INCARNATION         => ['name' => 'mapicontypes.spell_incarnation', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_SPELL_MISDIRECTION        => ['name' => 'mapicontypes.spell_misdirection', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_SPELL_TRICKS_OF_THE_TRADE => ['name' => 'mapicontypes.spell_tricks_of_the_trade', 'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_ROLE_TANK   => ['name' => 'mapicontypes.role_tank', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_ROLE_HEALER => ['name' => 'mapicontypes.role_healer', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_ROLE_DPS    => ['name' => 'mapicontypes.role_dps', 'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_CLASS_WARRIOR      => ['name' => 'mapicontypes.class_warrior', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_HUNTER       => ['name' => 'mapicontypes.class_hunter', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_DEATH_KNIGHT => ['name' => 'mapicontypes.class_death_knight', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_MAGE         => ['name' => 'mapicontypes.class_mage', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_PRIEST       => ['name' => 'mapicontypes.class_priest', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_MONK         => ['name' => 'mapicontypes.class_monk', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_ROGUE        => ['name' => 'mapicontypes.class_rogue', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_WARLOCK      => ['name' => 'mapicontypes.class_warlock', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_SHAMAN       => ['name' => 'mapicontypes.class_shaman', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_PALADIN      => ['name' => 'mapicontypes.class_paladin', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_DRUID        => ['name' => 'mapicontypes.class_druid', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_DEMON_HUNTER => ['name' => 'mapicontypes.class_demon_hunter', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CLASS_EVOKER       => ['name' => 'mapicontypes.class_evoker', 'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_CHEST        => ['name' => 'mapicontypes.chest', 'width' => 32, 'height' => 32],
            MapIconType::MAP_ICON_TYPE_CHEST_LOCKED => ['name' => 'mapicontypes.chest_locked', 'width' => 32, 'height' => 32],

            MapIconType::MAP_ICON_TYPE_MISTS_STATSHROOM  => ['name' => 'mapicontypes.mists_statshroom', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_MISTS_TOUGHSHROOM => ['name' => 'mapicontypes.mists_toughshroom', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_MISTS_OVERGROWN_ROOTS => ['name' => 'mapicontypes.mists_overgrown_roots', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_COT_SHADECASTER => ['name' => 'mapicontypes.cot_shadecaster', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_SV_IMBUED_IRON_ENERGY => ['name' => 'mapicontypes.sv_imbued_iron_energy', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_ARA_KARA_SILK_WRAP => ['name' => 'mapicontypes.ara_kara_silk_wrap', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_KARAZHAN_CRYPTS_SPIDER_NEST => ['name' => 'mapicontypes.karazhan_crypts_spider_nest', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_PRIORY_BLESSING_OF_THE_SACRED_FLAME => ['name' => 'mapicontypes.priory_blessing_of_the_sacred_flame', 'width' => 32, 'height' => 32, 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_FLOODGATE_WEAPONS_STOCKPILE_EXPLOSION => ['name' => 'mapicontypes.floodgate_weapons_stockpile_explosion', 'width' => 32, 'height' => 32, 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_GATE_OF_THE_SETTING_SUN_BRAZIER => ['name' => 'mapicontypes.gate_of_the_setting_sun_brazier', 'width' => 32, 'height' => 32, 'admin_only' => true],
        ];

        $mapIconTypeAttributes = [];
        foreach ($mapIconTypes as $key => $mapIconType) {
            $mapIconTypeAttributes[] = [
                'id'         => MapIconType::ALL[$key],
                'key'        => $key,
                'name'       => $mapIconType['name'],
                'width'      => $mapIconType['width'] ?? 32,
                'height'     => $mapIconType['height'] ?? 32,
                'admin_only' => $mapIconType['admin_only'] ?? 0,
            ];
        }

        MapIconType::from(DatabaseSeeder::getTempTableName(MapIconType::class))->insert($mapIconTypeAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [MapIconType::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
