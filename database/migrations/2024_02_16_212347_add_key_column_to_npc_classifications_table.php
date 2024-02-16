<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeyColumnToNpcClassificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('npc_classifications', function (Blueprint $table) {
            $table->renameColumn('shortname', 'key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('npc_classifications', function (Blueprint $table) {
            $table->renameColumn('key', 'shortname');
        });
    }
}
