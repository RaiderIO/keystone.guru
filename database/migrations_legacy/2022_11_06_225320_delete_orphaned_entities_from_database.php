<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::delete('
            DELETE `polylines`
            FROM polylines
                LEFT JOIN brushlines on polylines.id = brushlines.polyline_id
            WHERE model_class = "App\\Models\\Brushline" AND brushlines.id is null;
        ');

        DB::delete('
            DELETE `polylines`
            FROM polylines
                 LEFT JOIN paths on polylines.id = paths.polyline_id
            WHERE model_class = "App\\Models\\Path" AND paths.id is null;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
