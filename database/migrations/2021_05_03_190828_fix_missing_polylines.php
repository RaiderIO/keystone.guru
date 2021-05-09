<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Schema;

class FixMissingPolylines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // The paths that do not have a polyline after the previous migration are pretty much doomed.
        // I can add a new polyline for these paths so they don't get deleted, but then what? I'd need to either add dummy
        // points so the user can interact with the lines. But then you'd have a random line on your route somewhere, that
        // doesn't make sense. Not adding any points does not fix any issues either since the user cannot interact with them,
        // they may as well not exist then. As such, we delete them
        $affected = DB::delete('
            DELETE paths FROM paths
            LEFT JOIN polylines on paths.polyline_id = polylines.id
            WHERE polylines.id is null
        ');
        Log::info(sprintf('Deleted %s paths because they did not have a polyline', $affected));

        $affected = DB::delete('
            DELETE brushlines FROM brushlines
            LEFT JOIN polylines on brushlines.polyline_id = polylines.id
            WHERE polylines.id is null
        ');
        Log::info(sprintf('Deleted %s brushlines because they did not have a polyline', $affected));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
