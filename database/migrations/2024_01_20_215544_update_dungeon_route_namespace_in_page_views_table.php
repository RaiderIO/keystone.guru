<?php

use Illuminate\Database\Migrations\Migration;

class UpdateDungeonRouteNamespaceInPageViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        set_time_limit(-1);
        // I may regret running this query
        DB::update('UPDATE `page_views` SET `model_class` = "App\Models\DungeonRoute\DungeonRoute" WHERE `model_class` = "App\Models\DungeonRoute"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        set_time_limit(-1);
        DB::update('UPDATE `page_views` SET `model_class` = "App\Models\DungeonRoute" WHERE `model_class` = "App\Models\DungeonRoute\DungeonRoute"');
    }
}
