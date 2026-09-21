<?php

use App\Models\EnemyPack;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const int CHUNK_SIZE = 1000;

    /** The colour the front-end draws a pack with when it has none of its own */
    private const string DEFAULT_COLOR = '#5993D2';

    private const int DEFAULT_WEIGHT = 1;

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

        $this->backfillPolylines();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Packs created after up() ran only have their shape on the polyline
        DB::statement(<<<'SQL'
            UPDATE enemy_packs
            INNER JOIN polylines ON polylines.id = enemy_packs.polyline_id
            SET enemy_packs.vertices_json = polylines.vertices_json,
                enemy_packs.color = COALESCE(enemy_packs.color, polylines.color)
            WHERE enemy_packs.vertices_json IS NULL
            SQL);

        DB::table('polylines')->where('model_class', EnemyPack::class)->delete();

        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropIndex(['polyline_id']);
            $table->dropColumn('polyline_id');
            $table->text('vertices_json')->nullable(false)->change();
        });
    }

    /**
     * Creates one polyline per enemy pack that does not have one yet, walking the primary key in ranges so no
     * statement holds locks on more than a chunk of enemy_packs at a time. Safe to re-run.
     */
    private function backfillPolylines(): void
    {
        $minId = DB::table('enemy_packs')->whereNull('polyline_id')->min('id');
        $maxId = DB::table('enemy_packs')->whereNull('polyline_id')->max('id');

        if ($minId === null) {
            return;
        }

        for ($fromId = (int)$minId; $fromId <= (int)$maxId; $fromId += self::CHUNK_SIZE) {
            $toId = $fromId + self::CHUNK_SIZE - 1;

            DB::transaction(static function () use ($fromId, $toId) {
                DB::insert(<<<'SQL'
                    INSERT INTO polylines (model_id, model_class, color, color_animated, weight, vertices_json)
                    SELECT enemy_packs.id, ?, COALESCE(enemy_packs.color, ?), enemy_packs.color_animated, ?, enemy_packs.vertices_json
                    FROM enemy_packs
                    WHERE enemy_packs.id BETWEEN ? AND ?
                      AND enemy_packs.polyline_id IS NULL
                      AND NOT EXISTS (
                          SELECT 1 FROM polylines
                          WHERE polylines.model_class = ? AND polylines.model_id = enemy_packs.id
                      )
                    SQL, [
                    EnemyPack::class,
                    self::DEFAULT_COLOR,
                    self::DEFAULT_WEIGHT,
                    $fromId,
                    $toId,
                    EnemyPack::class,
                ]);

                DB::update(<<<'SQL'
                    UPDATE enemy_packs
                    INNER JOIN polylines ON polylines.model_id = enemy_packs.id AND polylines.model_class = ?
                    SET enemy_packs.polyline_id = polylines.id
                    WHERE enemy_packs.id BETWEEN ? AND ?
                      AND enemy_packs.polyline_id IS NULL
                    SQL, [EnemyPack::class, $fromId, $toId]);
            });
        }
    }
};
