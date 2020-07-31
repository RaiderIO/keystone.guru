<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPullGradientColumnToDungeonRoutesTable extends Migration
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
            $table->mediumText('pull_gradient')->after('demo');
            $table->boolean('pull_gradient_apply_always')->after('pull_gradient');
        });
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
            $table->dropColumn('pull_gradient_apply_always');
            $table->dropColumn('pull_gradient');
        });
    }
}
