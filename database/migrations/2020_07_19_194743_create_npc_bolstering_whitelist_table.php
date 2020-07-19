<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpcBolsteringWhitelistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('npc_bolstering_whitelist', function (Blueprint $table) {
            $table->id();
            $table->integer('npc_id');
            $table->integer('whitelist_npc_id');

            $table->index('npc_id');
            $table->index('whitelist_npc_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('npc_bolstering_whitelist');
    }
}
