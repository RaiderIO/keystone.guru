<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeMapIconTypeIdColumnNullableInMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->integer('map_icon_type_id')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `map_icons` SET `map_icon_type_id` = null WHERE `map_icon_type_id` <= 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->integer('map_icon_type_id')->nullable(false)->default(-1)->change();
        });
    }
}
