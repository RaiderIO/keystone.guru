<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUserIdNullableOnLiveSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('live_sessions', function (Blueprint $table)
        {
            $table->integer('user_id')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('live_sessions', function (Blueprint $table)
        {
            $table->integer('user_id')->nullable(false)->change();
        });
    }
}
