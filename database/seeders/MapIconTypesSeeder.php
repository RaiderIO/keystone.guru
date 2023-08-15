<?php

namespace Database\Seeders;

use App\Models\MapIconType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapIconTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();
        $this->command->info('Adding known Map Icon Types');

        $mapIconTypes = [
            MapIconType::MAP_ICON_TYPE_UNKNOWN                   => ['name' => 'mapicontypes.unknown'],
            MapIconType::MAP_ICON_TYPE_COMMENT                   => ['name' => 'mapicontypes.comment'],
            MapIconType::MAP_ICON_TYPE_DOOR                      => ['name' => 'mapicontypes.door', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_DOWN                 => ['name' => 'mapicontypes.door_down', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_LEFT                 => ['name' => 'mapicontypes.door_left', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_LOCKED               => ['name' => 'mapicontypes.door_locked', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_RIGHT                => ['name' => 'mapicontypes.door_right', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOOR_UP                   => ['name' => 'mapicontypes.door_up', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_DOT_YELLOW                => ['name' => 'mapicontypes.dot_yellow'],
            MapIconType::MAP_ICON_TYPE_DUNGEON_START             => ['name' => 'mapicontypes.dungeon_start', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_GATEWAY                   => ['name' => 'mapicontypes.gateway'],
            MapIconType::MAP_ICON_TYPE_GRAVEYARD                 => ['name' => 'mapicontypes.graveyard', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_GREASEBOT                 => ['name' => 'mapicontypes.greasebot', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_SHOCKBOT                  => ['name' => 'mapicontypes.shockbot', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_WARLOCK_GATEWAY           => ['name' => 'mapicontypes.warlock_gateway'],
            MapIconType::MAP_ICON_TYPE_WELDINGBOT                => ['name' => 'mapicontypes.weldingbot', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL   => ['name' => 'mapicontypes.awakened_obelisk_brutal', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED   => ['name' => 'mapicontypes.awakened_obelisk_cursed', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED  => ['name' => 'mapicontypes.awakened_obelisk_defiled', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC => ['name' => 'mapicontypes.awakened_obelisk_entropic', 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_SKIP_FLIGHT   => ['name' => 'mapicontypes.skip_flight', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_SKIP_TELEPORT => ['name' => 'mapicontypes.skip_teleport', 'admin_only' => true],
            MapIconType::MAP_ICON_TYPE_SKIP_WALK     => ['name' => 'mapicontypes.skip_walk', 'admin_only' => true],

            MapIconType::MAP_ICON_TYPE_RAID_MARKER_STAR     => ['name' => 'mapicontypes.raid_marker_star'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_CIRCLE   => ['name' => 'mapicontypes.raid_marker_circle'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_DIAMOND  => ['name' => 'mapicontypes.raid_marker_diamond'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_TRIANGLE => ['name' => 'mapicontypes.raid_marker_triangle'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_MOON     => ['name' => 'mapicontypes.raid_marker_moon'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_SQUARE   => ['name' => 'mapicontypes.raid_marker_square'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_CROSS    => ['name' => 'mapicontypes.raid_marker_cross'],
            MapIconType::MAP_ICON_TYPE_RAID_MARKER_SKULL    => ['name' => 'mapicontypes.raid_marker_skull'],

            MapIconType::MAP_ICON_TYPE_SPELL_BLOODLUST             => ['name' => 'mapicontypes.spell_bloodlust'],
            MapIconType::MAP_ICON_TYPE_SPELL_HEROISM               => ['name' => 'mapicontypes.spell_heroism'],
            MapIconType::MAP_ICON_TYPE_SPELL_SHADOWMELD            => ['name' => 'mapicontypes.spell_shadowmeld'],
            MapIconType::MAP_ICON_TYPE_SPELL_SHROUD_OF_CONCEALMENT => ['name' => 'mapicontypes.spell_shroud_of_concealment'],

            MapIconType::MAP_ICON_TYPE_ITEM_INVISIBILITY                 => ['name' => 'mapicontypes.item_invisibility'],
            MapIconType::MAP_ICON_TYPE_ITEM_DRUMS_OF_SPEED               => ['name' => 'mapicontypes.item_drums_of_speed'],
            MapIconType::MAP_ICON_TYPE_ITEM_FREE_ACTION_POTION           => ['name' => 'mapicontypes.item_free_action_potion'],
            MapIconType::MAP_ICON_TYPE_ITEM_GLOBAL_THERMAL_SAPPER_CHARGE => ['name' => 'mapicontypes.item_global_thermal_sapper_charge'],
            MapIconType::MAP_ICON_TYPE_ITEM_ROCKET_BOOTS_XTREME          => ['name' => 'mapicontypes.item_rocket_boots_xtreme'],

            MapIconType::MAP_ICON_TYPE_QUESTION_YELLOW => ['name' => 'mapicontypes.question_yellow'],
            MapIconType::MAP_ICON_TYPE_QUESTION_BLUE   => ['name' => 'mapicontypes.question_blue'],
            MapIconType::MAP_ICON_TYPE_QUESTION_ORANGE => ['name' => 'mapicontypes.question_orange'],

            MapIconType::MAP_ICON_TYPE_EXCLAMATION_YELLOW => ['name' => 'mapicontypes.exclamation_yellow'],
            MapIconType::MAP_ICON_TYPE_EXCLAMATION_BLUE   => ['name' => 'mapicontypes.exclamation_blue'],
            MapIconType::MAP_ICON_TYPE_EXCLAMATION_ORANGE => ['name' => 'mapicontypes.exclamation_orange'],

            MapIconType::MAP_ICON_TYPE_NEONBUTTON_BLUE      => ['name' => 'mapicontypes.neonbutton_blue'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_CYAN      => ['name' => 'mapicontypes.neonbutton_cyan'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_GREEN     => ['name' => 'mapicontypes.neonbutton_green'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_ORANGE    => ['name' => 'mapicontypes.neonbutton_orange'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_PINK      => ['name' => 'mapicontypes.neonbutton_pink'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_PURPLE    => ['name' => 'mapicontypes.neonbutton_purple'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_RED       => ['name' => 'mapicontypes.neonbutton_red'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_YELLOW    => ['name' => 'mapicontypes.neonbutton_yellow'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_DARKRED   => ['name' => 'mapicontypes.neonbutton_darkred'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_DARKGREEN => ['name' => 'mapicontypes.neonbutton_darkgreen'],
            MapIconType::MAP_ICON_TYPE_NEONBUTTON_DARKBLUE  => ['name' => 'mapicontypes.neonbutton_darkblue'],

            MapIconType::MAP_ICON_TYPE_SPELL_MIND_SOOTHE => ['name' => 'mapicontypes.spell_mind_soothe'],
            MapIconType::MAP_ICON_TYPE_SPELL_COMBUSTION  => ['name' => 'mapicontypes.spell_combustion'],

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
        ];

        foreach ($mapIconTypes as $key => $mapIconType) {
            // Just in case it doesn't exist
            if (isset($mapIconType['width']) && isset($mapIconType['height'])) {
                $imageSize = [$mapIconType['width'], $mapIconType['height']];
            } else {
                $filePath = resource_path(sprintf('assets/images/mapicon/%s.png', $key));
                if (file_exists($filePath)) {
                    $imageSize = getimagesize($filePath);
                } else {
                    $this->command->warn(sprintf('Unable to find file %s', $filePath));
                    $imageSize = [16, 16];
                }

                // Overrides
                if (isset($mapIconType['width'])) {
                    $imageSize[0] = $mapIconType['width'];
                }

                if (isset($mapIconType['height'])) {
                    $imageSize[1] = $mapIconType['height'];
                }
            }

            MapIconType::create([
                'id'         => MapIconType::ALL[$key],
                'key'        => $key,
                'name'       => $mapIconType['name'],
                'width'      => $imageSize[0],
                'height'     => $imageSize[1],
                'admin_only' => $mapIconType['admin_only'] ?? 0,
            ]);
        }
    }

    private function rollback()
    {
        DB::table('map_icon_types')->truncate();
    }
}
