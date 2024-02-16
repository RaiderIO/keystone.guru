<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeyColumnToNpcClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('npc_classes', function (Blueprint $table) {
            $table->string('key')->after('name');

            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('npc_classes', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }
}
