<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBurstingToNpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->boolean('bursting')->after('truesight')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn('bursting');
        });
    }
}
