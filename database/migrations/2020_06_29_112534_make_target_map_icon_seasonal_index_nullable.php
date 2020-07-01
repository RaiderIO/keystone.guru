<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTargetMapIconSeasonalIndexNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->integer('target_map_icon_seasonal_index')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->integer('target_map_icon_seasonal_index')->nullable(false)->change();
        });
    }
}
