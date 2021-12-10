<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimewalkingEventAffixGroupCouplingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timewalking_event_affix_group_couplings', function (Blueprint $table) {
            $table->id();
            $table->integer('affix_id');
            $table->integer('timewalking_event_affix_group_id');

            $table->index('affix_id');
            // timewalking_affix_group_couplings_timewalking_affix_group_id_index
            $table->index('timewalking_event_affix_group_id', 'tweagc_timewalking_affix_group_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timewalking_event_affix_group_couplings');
    }
}
