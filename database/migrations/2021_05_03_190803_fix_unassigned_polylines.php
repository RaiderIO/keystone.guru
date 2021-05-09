<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixUnassignedPolylines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This will take the polylines as a source of truth and apply it to the polylines
        $affected = DB::update('
            UPDATE paths
            JOIN polylines on paths.id = polylines.model_id
            SET paths.polyline_id = polylines.id
            WHERE polylines.id != paths.polyline_id
            AND polylines.model_class = :modelClass
        ', ['modelClass' => 'App\\Models\\Path']);
        Log::info(sprintf('Fixed %s paths because the polyline they were linked to did not match', $affected));

        $affected = DB::update('
            UPDATE brushlines
            JOIN polylines on brushlines.id = polylines.model_id
            SET brushlines.polyline_id = polylines.id
            WHERE polylines.id != brushlines.polyline_id
            AND polylines.model_class = :modelClass
        ', ['modelClass' => 'App\\Models\\Brushline']);
        Log::info(sprintf('Fixed %s brushlines because the polyline they were linked to did not match', $affected));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back
    }
}
