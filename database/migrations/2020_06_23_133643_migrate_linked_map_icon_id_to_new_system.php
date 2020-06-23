<?php

use Illuminate\Database\Migrations\Migration;

class MigrateLinkedMapIconIdToNewSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $linkedMapIcons = DB::table('map_icons')->select(['id', 'linked_map_icon_id'])->whereNotNull('linked_map_icon_id')->get();
        foreach ($linkedMapIcons as $linkedMapIcon) {
            /** @var \App\Models\MapIcon $awakenedObeliskMapIcon */
            $awakenedObeliskMapIcon = DB::table('map_icons')->find($linkedMapIcon->linked_map_icon_id);

            // Insert any existing links into the links table
            DB::table('map_object_to_awakened_obelisk_links')->insert([
                'source_map_object_id'           => $linkedMapIcon->id,
                'source_map_object_class_name'   => 'App\Models\MapIcon',
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
