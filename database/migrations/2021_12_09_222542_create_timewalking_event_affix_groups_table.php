<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimewalkingEventAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timewalking_event_affix_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('timewalking_event_id');
            $table->integer('seasonal_index')->nullable(true);

            $table->index('timewalking_event_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timewalking_event_affix_groups');
    }
}
