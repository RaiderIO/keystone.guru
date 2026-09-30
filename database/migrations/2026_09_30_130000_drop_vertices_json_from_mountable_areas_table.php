<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $areasWithoutPolyline = DB::table('mountable_areas')->whereNull('polyline_id')->count();
        if ($areasWithoutPolyline > 0) {
            throw new RuntimeException(sprintf(
                'Refusing to drop mountable_areas.vertices_json: %d mountable area(s) have no polyline_id',
                $areasWithoutPolyline,
            ));
        }

        Schema::table('mountable_areas', function (Blueprint $table) {
            $table->dropColumn('vertices_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mountable_areas', function (Blueprint $table) {
            $table->text('vertices_json')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE mountable_areas
            INNER JOIN polylines ON polylines.id = mountable_areas.polyline_id
            SET mountable_areas.vertices_json = polylines.vertices_json
            SQL);
    }
};
