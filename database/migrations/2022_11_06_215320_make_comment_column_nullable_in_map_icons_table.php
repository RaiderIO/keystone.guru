<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCommentColumnNullableInMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->text('comment')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `map_icons` SET `comment` = null WHERE `comment` = ""');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->text('comment')->nullable(false)->default('')->change();
        });
    }
}
