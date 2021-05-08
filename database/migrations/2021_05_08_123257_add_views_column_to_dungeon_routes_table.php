<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddViewsColumnToDungeonRoutesTable extends Migration
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
            $table->integer('views')->after('pull_gradient_apply_always')->default(0);
            $table->index(['views']);
        });

        DB::update('
            UPDATE dungeon_routes, (
                SELECT model_id, count(0) as views
                FROM page_views
                WHERE page_views.model_class = :modelClass
                GROUP BY page_views.model_id
            ) as page_views
            SET dungeon_routes.views = page_views.views
            WHERE dungeon_routes.id = page_views.model_id
        ', ['modelClass' => 'App\\Models\\DungeonRoute']);
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
            $table->dropColumn('views');
            $table->dropIndex(['views']);
        });
    }
}
