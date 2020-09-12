<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDefaultColumnOnFloorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Must be two separate statements, otherwise it complains
        Schema::table('floors', function (Blueprint $table)
        {
            $table->dropColumn('default');
        });
        Schema::table('floors', function (Blueprint $table)
        {
            // Needs to be after name
            $table->boolean('default')->default(false)->after('name');
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
            $table->boolean('default')->default(false);
        });
    }
}
