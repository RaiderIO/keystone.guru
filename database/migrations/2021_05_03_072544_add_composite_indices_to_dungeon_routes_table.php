<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompositeIndicesToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->index(['published_state_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex(['published_state_id', 'expires_at']);
        });
    }
}
