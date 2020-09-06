<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeasonalTypeColumnToEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemies', function (Blueprint $table)
        {
            $table->enum('seasonal_type', ['awakened', 'inspiring'])->after('mdt_id')->nullable(true);
        });

        DB::update('
            UPDATE `enemies` SET `seasonal_type` = "awakened" WHERE `seasonal_index` IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemies', function (Blueprint $table)
        {
            $table->dropColumn('seasonal_type');
        });
    }
}
