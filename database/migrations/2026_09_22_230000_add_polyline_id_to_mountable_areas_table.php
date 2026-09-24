<?php

use App\Models\MountableArea;
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
        Schema::table('mountable_areas', function (Blueprint $table) {
            $table->integer('polyline_id')->nullable()->after('speed');
            $table->text('vertices_json')->nullable()->change();

            $table->index('polyline_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Once the seeder ran against the new JSON, a mountable area's shape lives only on its polyline
        DB::statement(<<<'SQL'
            UPDATE mountable_areas
            INNER JOIN polylines ON polylines.id = mountable_areas.polyline_id
            SET mountable_areas.vertices_json = polylines.vertices_json
            SQL);

        DB::table('polylines')->where('model_class', MountableArea::class)->delete();

        Schema::table('mountable_areas', function (Blueprint $table) {
            $table->dropIndex(['polyline_id']);
            $table->dropColumn('polyline_id');
            $table->text('vertices_json')->nullable(false)->change();
        });
    }
};
