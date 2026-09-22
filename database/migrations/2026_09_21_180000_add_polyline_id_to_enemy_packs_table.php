<?php

use App\Models\EnemyPack;
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
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->integer('polyline_id')->nullable()->after('label');
            $table->text('vertices_json')->nullable()->change();

            $table->index('polyline_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Once the seeder ran against the new JSON, a pack's shape and colour live only on its polyline
        DB::statement(<<<'SQL'
            UPDATE enemy_packs
            INNER JOIN polylines ON polylines.id = enemy_packs.polyline_id
            SET enemy_packs.vertices_json = polylines.vertices_json,
                enemy_packs.color = polylines.color,
                enemy_packs.color_animated = polylines.color_animated
            SQL);

        DB::table('polylines')->where('model_class', EnemyPack::class)->delete();

        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropIndex(['polyline_id']);
            $table->dropColumn('polyline_id');
            $table->text('vertices_json')->nullable(false)->change();
        });
    }
};
