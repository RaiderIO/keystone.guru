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
            'unknown'                   => ['name' => ''],
            'comment'                   => ['name' => 'Comment'],
            'door'                      => ['name' => 'Door', 'admin_only' => true],
            'door_down'                 => ['name' => 'Door Down', 'admin_only' => true],
            'door_left'                 => ['name' => 'Door Left', 'admin_only' => true],
            'door_locked'               => ['name' => 'Door Locked', 'admin_only' => true],
            'door_right'                => ['name' => 'Door Right', 'admin_only' => true],
            'door_up'                   => ['name' => 'Door Up', 'admin_only' => true],
            'dot_yellow'                => ['name' => 'Yellow Dot'],
            'dungeon_start'             => ['name' => 'Dungeon Start', 'admin_only' => true],
            'gateway'                   => ['name' => 'Gateway'],
            'graveyard'                 => ['name' => 'Graveyard', 'admin_only' => true],
            'greasebot'                 => ['name' => 'Grease bot (haste)', 'admin_only' => true],
            'shockbot'                  => ['name' => 'Shock bot (damage)', 'admin_only' => true],
            'warlock_gateway'           => ['name' => 'Warlock Gateway'],
            'weldingbot'                => ['name' => 'Welding bot (-damage taken/+healing received)', 'admin_only' => true],
            'awakened_obelisk_brutal'   => ['name' => 'Brutal Spire of Ny\'alotha (Urg\'roth, Breaker of Heroes)', 'admin_only' => true],
            'awakened_obelisk_cursed'   => ['name' => 'Cursed Spire of Ny\'alotha (Voidweaver Mal\'thir)', 'admin_only' => true],
            'awakened_obelisk_defiled'  => ['name' => 'Defiled Spire of Ny\'alotha (Blood of the Corruptor)', 'admin_only' => true],
            'awakened_obelisk_entropic' => ['name' => 'Entropic Spire of Ny\'alotha (Samh\'rek, Beckoner of Chaos)', 'admin_only' => true],

            'skip_flight'   => ['name' => 'Skip', 'admin_only' => true],
            'skip_teleport' => ['name' => 'Skip', 'admin_only' => true],
            'skip_walk'     => ['name' => 'Skip', 'admin_only' => true],

            'raid_marker_star'     => ['name' => 'Star'],
            'raid_marker_circle'   => ['name' => 'Circle'],
            'raid_marker_diamond'  => ['name' => 'Diamond'],
            'raid_marker_triangle' => ['name' => 'Triangle'],
            'raid_marker_moon'     => ['name' => 'Moon'],
            'raid_marker_square'   => ['name' => 'Square'],
            'raid_marker_cross'    => ['name' => 'Cross'],
            'raid_marker_skull'    => ['name' => 'Skull'],

            'spell_bloodlust'             => ['name' => 'Bloodlust'],
            'spell_heroism'               => ['name' => 'Heroism'],
            'spell_shadowmeld'            => ['name' => 'Shadowmeld'],
            'spell_shroud_of_concealment' => ['name' => 'Shroud of Concealment'],

            'item_invisibility' => ['name' => 'Invisibility Potion'],

            'question_yellow' => ['name' => 'Question'],
            'question_blue'   => ['name' => 'Question'],
            'question_orange' => ['name' => 'Question'],

            'exclamation_yellow' => ['name' => 'Exclamation'],
            'exclamation_blue'   => ['name' => 'Exclamation'],
            'exclamation_orange' => ['name' => 'Exclamation'],

            'neonbutton_blue'      => ['name' => 'Button blue'],
            'neonbutton_cyan'      => ['name' => 'Button cyan'],
            'neonbutton_green'     => ['name' => 'Button green'],
            'neonbutton_orange'    => ['name' => 'Button orange'],
            'neonbutton_pink'      => ['name' => 'Button pink'],
            'neonbutton_purple'    => ['name' => 'Button purple'],
            'neonbutton_red'       => ['name' => 'Button red'],
            'neonbutton_yellow'    => ['name' => 'Button yellow'],
            'neonbutton_darkred'   => ['name' => 'Button dark red'],
            'neonbutton_darkgreen' => ['name' => 'Button dark green'],
            'neonbutton_darkblue'  => ['name' => 'Button dark blue'],

            'spell_mind_soothe' => ['name' => 'Mind Soothe'],
            'spell_combustion'  => ['name' => 'Combustion'],

            'covenant_kyrian'     => ['name' => 'Kyrian', 'width' => 32, 'height' => 32],
            'covenant_necrolords' => ['name' => 'Necrolords', 'width' => 32, 'height' => 32],
            'covenant_nightfae'   => ['name' => 'Night Fae', 'width' => 32, 'height' => 32],
            'covenant_venthyr'    => ['name' => 'Venthyr', 'width' => 32, 'height' => 32],

            'portal_blue'   => ['name' => 'Portal', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_green'  => ['name' => 'Portal', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_orange' => ['name' => 'Portal', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_pink'   => ['name' => 'Portal', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'portal_red'    => ['name' => 'Portal', 'width' => 32, 'height' => 32, 'admin_only' => true],

            'nw_item_anima'   => ['name' => 'Anima', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_goliath' => ['name' => 'Goliath', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_hammer'  => ['name' => 'Hammer', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_shield'  => ['name' => 'Shield', 'width' => 32, 'height' => 32, 'admin_only' => true],
            'nw_item_spear'   => ['name' => 'Spear', 'width' => 32, 'height' => 32, 'admin_only' => true],
        ];

        foreach ($mapIconTypes as $key => $mapIconType) {
            $mapIconTypeModel       = new MapIconType();
            $mapIconTypeModel->key  = $key;
            $mapIconTypeModel->name = $mapIconType['name'];

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
            $mapIconTypeModel->width      = $imageSize[0];
            $mapIconTypeModel->height     = $imageSize[1];
            $mapIconTypeModel->admin_only = isset($mapIconType['admin_only']) ? $mapIconType['admin_only'] : 0;
            $mapIconTypeModel->save();
        }
    }

    private function _rollback()
    {
        DB::table('map_icon_types')->truncate();
    }
}
