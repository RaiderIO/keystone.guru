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
        $this->_rollback();
        $this->command->info('Adding known Map Icon Types');

        $mapIconTypes = [
            'unknown'                   => ['name' => 'mapicontypes.unknown'],
            'comment'                   => ['name' => 'mapicontypes.comment'],
            'door'                      => ['name' => 'mapicontypes.door', 'admin_only' => true],
            'door_down'                 => ['name' => 'mapicontypes.door_down', 'admin_only' => true],
            'door_left'                 => ['name' => 'mapicontypes.door_left', 'admin_only' => true],
            'door_locked'               => ['name' => 'mapicontypes.door_locked', 'admin_only' => true],
            'door_right'                => ['name' => 'mapicontypes.door_right', 'admin_only' => true],
            'door_up'                   => ['name' => 'mapicontypes.door_up', 'admin_only' => true],
            'dot_yellow'                => ['name' => 'mapicontypes.dot_yellow'],
            'dungeon_start'             => ['name' => 'mapicontypes.dungeon_start', 'admin_only' => true],
            'gateway'                   => ['name' => 'mapicontypes.gateway'],
            'graveyard'                 => ['name' => 'mapicontypes.graveyard', 'admin_only' => true],
            'greasebot'                 => ['name' => 'mapicontypes.greasebot', 'admin_only' => true],
            'shockbot'                  => ['name' => 'mapicontypes.shockbot', 'admin_only' => true],
            'warlock_gateway'           => ['name' => 'mapicontypes.warlock_gateway'],
            'weldingbot'                => ['name' => 'mapicontypes.weldingbot', 'admin_only' => true],
            'awakened_obelisk_brutal'   => ['name' => 'mapicontypes.awakened_obelisk_brutal', 'admin_only' => true],
            'awakened_obelisk_cursed'   => ['name' => 'mapicontypes.awakened_obelisk_cursed', 'admin_only' => true],
            'awakened_obelisk_defiled'  => ['name' => 'mapicontypes.awakened_obelisk_defiled', 'admin_only' => true],
            'awakened_obelisk_entropic' => ['name' => 'mapicontypes.awakened_obelisk_entropic', 'admin_only' => true],

            'skip_flight'   => ['name' => 'mapicontypes.skip_flight', 'admin_only' => true],
            'skip_teleport' => ['name' => 'mapicontypes.skip_teleport', 'admin_only' => true],
            'skip_walk'     => ['name' => 'mapicontypes.skip_walk', 'admin_only' => true],

            'raid_marker_star'     => ['name' => 'mapicontypes.raid_marker_star'],
            'raid_marker_circle'   => ['name' => 'mapicontypes.raid_marker_circle'],
            'raid_marker_diamond'  => ['name' => 'mapicontypes.raid_marker_diamond'],
            'raid_marker_triangle' => ['name' => 'mapicontypes.raid_marker_triangle'],
            'raid_marker_moon'     => ['name' => 'mapicontypes.raid_marker_moon'],
            'raid_marker_square'   => ['name' => 'mapicontypes.raid_marker_square'],
            'raid_marker_cross'    => ['name' => 'mapicontypes.raid_marker_cross'],
            'raid_marker_skull'    => ['name' => 'mapicontypes.raid_marker_skull'],

            'spell_bloodlust'             => ['name' => 'mapicontypes.spell_bloodlust'],
            'spell_heroism'               => ['name' => 'mapicontypes.spell_heroism'],
            'spell_shadowmeld'            => ['name' => 'mapicontypes.spell_shadowmeld'],
            'spell_shroud_of_concealment' => ['name' => 'mapicontypes.spell_shroud_of_concealment'],

            'item_invisibility' => ['name' => 'mapicontypes.item_invisibility'],

            'question_yellow' => ['name' => 'mapicontypes.question_yellow'],
            'question_blue'   => ['name' => 'mapicontypes.question_blue'],
            'question_orange' => ['name' => 'mapicontypes.question_orange'],

            'exclamation_yellow' => ['name' => 'mapicontypes.exclamation_yellow'],
            'exclamation_blue'   => ['name' => 'mapicontypes.exclamation_blue'],
            'exclamation_orange' => ['name' => 'mapicontypes.exclamation_orange'],

            'neonbutton_blue'      => ['name' => 'mapicontypes.neonbutton_blue'],
            'neonbutton_cyan'      => ['name' => 'mapicontypes.neonbutton_cyan'],
            'neonbutton_green'     => ['name' => 'mapicontypes.neonbutton_green'],
            'neonbutton_orange'    => ['name' => 'mapicontypes.neonbutton_orange'],
            'neonbutton_pink'      => ['name' => 'mapicontypes.neonbutton_pink'],
            'neonbutton_purple'    => ['name' => 'mapicontypes.neonbutton_purple'],
            'neonbutton_red'       => ['name' => 'mapicontypes.neonbutton_red'],
            'neonbutton_yellow'    => ['name' => 'mapicontypes.neonbutton_yellow'],
            'neonbutton_darkred'   => ['name' => 'mapicontypes.neonbutton_darkred'],
            'neonbutton_darkgreen' => ['name' => 'mapicontypes.neonbutton_darkgreen'],
            'neonbutton_darkblue'  => ['name' => 'mapicontypes.neonbutton_darkblue'],

            'spell_mind_soothe' => ['name' => 'mapicontypes.spell_mind_soothe'],
            'spell_combustion'  => ['name' => 'mapicontypes.spell_combustion'],

            'covenant_kyrian'     => ['name' => 'mapicontypes.covenant_kyrian', 'width' => 32, 'height' => 32],
            'covenant_necrolords' => ['name' => 'mapicontypes.covenant_necrolords', 'width' => 32, 'height' => 32],
            'covenant_nightfae'   => ['name' => 'mapicontypes.covenant_nightfae', 'width' => 32, 'height' => 32],
            'covenant_venthyr'    => ['name' => 'mapicontypes.covenant_venthyr', 'width' => 32, 'height' => 32],

            'portal_blue'   => ['name' => 'mapicontypes.portal_blue', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_green'  => ['name' => 'mapicontypes.portal_green', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_orange' => ['name' => 'mapicontypes.portal_orange', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_pink'   => ['name' => 'mapicontypes.portal_pink', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_red'    => ['name' => 'mapicontypes.portal_red', 'width' => 32, 'height' => 32, 'admin_only' => true],

            'nw_item_anima'   => ['name' => 'mapicontypes.nw_item_anima', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_goliath' => ['name' => 'mapicontypes.nw_item_goliath', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_hammer'  => ['name' => 'mapicontypes.nw_item_hammer', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_shield'  => ['name' => 'mapicontypes.nw_item_shield', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_spear'   => ['name' => 'mapicontypes.nw_item_spear', 'width' => 32, 'height' => 32, 'admin_only' => true],

            'spell_incarnation' => ['name' => 'mapicontypes.spell_incarnation', 'width' => 32, 'height' => 32,],
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
                'key'        => $key,
                'name'       => $mapIconType['name'],
                'width'      => $imageSize[0],
                'height'     => $imageSize[1],
                'admin_only' => $mapIconType['admin_only'] ?? 0,
            ]);
        }
    }

    private function _rollback()
    {
        DB::table('map_icon_types')->truncate();
    }
}
