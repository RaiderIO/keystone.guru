<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPermanentTooltipColumnToMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->boolean('permanent_tooltip')->after('comment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->dropColumn('permanent_tooltip');
        });
    }
}
