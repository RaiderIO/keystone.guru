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
        $packsWithoutPolyline = DB::table('enemy_packs')->whereNull('polyline_id')->count();
        if ($packsWithoutPolyline > 0) {
            throw new RuntimeException(sprintf(
                'Refusing to drop enemy_packs geometry columns: %d enemy pack(s) have no polyline_id',
                $packsWithoutPolyline,
            ));
        }

        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropColumn(['vertices_json', 'color', 'color_animated']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->text('vertices_json')->nullable()->after('label');
            $table->string('color', 255)->nullable()->after('vertices_json');
            $table->string('color_animated', 255)->nullable()->after('color');
        });

        DB::statement(<<<'SQL'
            UPDATE enemy_packs
            INNER JOIN polylines ON polylines.id = enemy_packs.polyline_id
            SET enemy_packs.vertices_json = polylines.vertices_json,
                enemy_packs.color = polylines.color,
                enemy_packs.color_animated = polylines.color_animated
            SQL);
    }
};
