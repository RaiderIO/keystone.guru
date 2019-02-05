<?php

use Illuminate\Database\Migrations\Migration;

class FixWaycrestManorFloorOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Swap 60 and 61
        /** @var \App\Models\Floor $upstairs */
        $upstairs = \App\Models\Floor::find(60);
        /** @var \App\Models\Floor $grandFoyer */
        $grandFoyer = \App\Models\Floor::find(61);

        // Verify we're dealing with the real deal (really swapped, not fixed already)
        if ($upstairs instanceof \Illuminate\Database\Eloquent\Model &&
            $grandFoyer instanceof \Illuminate\Database\Eloquent\Model &&
            $upstairs->index === 2 && $grandFoyer->index === 1) {
            // Move existing user generated items away
            DB::table('map_comments')->where('floor_id', $upstairs->id)->update(['floor_id' => 999]);
            DB::table('routes')->where('floor_id', $upstairs->id)->update(['floor_id' => 999]);
            DB::table('kill_zones')->where('floor_id', $upstairs->id)->update(['floor_id' => 999]);

            // Move comments to new Grand Foyer
            DB::table('map_comments')->where('floor_id', $grandFoyer->id)->update(['floor_id' => 60]);
            DB::table('routes')->where('floor_id', $grandFoyer->id)->update(['floor_id' => 60]);
            DB::table('kill_zones')->where('floor_id', $grandFoyer->id)->update(['floor_id' => 60]);

            // Move these items to their new floor
            DB::table('map_comments')->where('floor_id', 999)->update(['floor_id' => 61]);
            DB::table('routes')->where('floor_id', 999)->update(['floor_id' => 61]);
            DB::table('kill_zones')->where('floor_id', 999)->update(['floor_id' => 61]);
        }

        // Re-seed; this will set the upstairs and grand foyer to their correct IDs again
        Artisan::call('db:seed', array('--class' => 'DungeonsSeeder', '--database' => 'migrate', '--force' => true));
        // Re-seed the enemies, their IDs are already fixed and just need to be re-imported
        Artisan::call('db:seed', array('--class' => 'DungeonDataSeeder', '--database' => 'migrate', '--force' => true));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No way Jose
    }
}
