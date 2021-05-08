<?php

use App\Models\DungeonRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPopularityColumnToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->integer('popularity')->after('views')->default(0);
            $table->index(['popularity']);
        });

        DB::update('
            UPDATE dungeon_routes, (
                SELECT model_id, count(0) as views
                FROM page_views
                WHERE page_views.model_class = :modelClass
                AND page_views.created_at > :popularityDate
                GROUP BY page_views.model_id
            ) as page_views
            SET dungeon_routes.popularity = page_views.views
            WHERE dungeon_routes.id = page_views.model_id
        ', [
            'modelClass'     => DungeonRoute::class,
            'popularityDate' => now()->subDays(config('keystoneguru.discover.service.popular_days'))->toDateTimeString()
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dropColumn('popularity');
            $table->dropIndex(['popularity']);
        });
    }
}
