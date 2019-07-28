<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNpcClassToNpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->integer('npc_class_id')->default(1)->after('npc_type_id');
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
            $table->dropColumn('npc_class_id');
        });
    }
}
