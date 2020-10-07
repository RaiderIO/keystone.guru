<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMinAndMaxEnemySizeToFloorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('floors', function (Blueprint $table)
        {
            $table->integer('min_enemy_size')->after('default')->nullable(true);
            $table->integer('max_enemy_size')->after('min_enemy_size')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('floors', function (Blueprint $table)
        {
            $table->dropColumn('min_enemy_size');
            $table->dropColumn('max_enemy_size');
        });
    }
}
