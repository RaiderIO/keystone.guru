<?php

use App\Models\MapIcon;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

class MigrateLinkedMapIconIdToNewSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var Collection|MapIcon[] $linkedMapIcons */
        $linkedMapIcons = DB::table('map_icons')->select(['*'])->whereNotNull('linked_map_icon_id')->get();
        foreach ($linkedMapIcons as $linkedMapIcon) {
            /** @var MapIcon $awakenedObeliskMapIcon */
            $awakenedObeliskMapIcon = DB::table('map_icons')->find($linkedMapIcon->linked_map_icon_id);

            // Insert any existing links into the links table
            DB::table('map_object_to_awakened_obelisk_links')->insert([
                'source_map_object_id'           => $linkedMapIcon->id,
                'source_map_object_class_name'   => 'App\Models\MapIcon',
                'target_map_icon_type_id'        => $awakenedObeliskMapIcon->map_icon_type_id,
                'target_map_icon_seasonal_index' => $awakenedObeliskMapIcon->seasonal_index,
            ]);

            // Create a new Path that was normally generated and used locally
            DB::table('polylines')->insert([
                'model_id'      => -1,
                'model_class'   => 'App\Models\Path',
                'color'         => '#80FF1A',
                'weight'        => 3,
                'vertices_json' => json_encode([
                    [
                        'lat' => $awakenedObeliskMapIcon->lat,
                        'lng' => $awakenedObeliskMapIcon->lng,
                    ], [
                        'lat' => $linkedMapIcon->lat,
                        'lng' => $linkedMapIcon->lng,
                    ]
                ])
            ]);

            $polylineId = DB::getPdo()->lastInsertId();

            DB::table('paths')->insert([
                'dungeon_route_id' => $linkedMapIcon->dungeon_route_id,
                'floor_id'         => $linkedMapIcon->floor_id,
                'polyline_id'      => $polylineId,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now(),
            ]);

            $pathId = DB::getPdo()->lastInsertId();

            DB::table('polylines')->where('id', $polylineId)->update([
                'model_id'      => $pathId,
                'model_class'   => 'App\Models\Path',
            ]);

            DB::table('map_object_to_awakened_obelisk_links')->insert([
                'source_map_object_id'           => $pathId,
                'source_map_object_class_name'   => 'App\Models\Polyline',
                'target_map_icon_type_id'        => $awakenedObeliskMapIcon->map_icon_type_id,
                'target_map_icon_seasonal_index' => $awakenedObeliskMapIcon->seasonal_index,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Table should be empty when downgrading
        DB::table('map_object_to_awakened_obelisk_links')->truncate();
    }
}
