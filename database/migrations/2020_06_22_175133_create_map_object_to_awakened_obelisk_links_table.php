<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapObjectToAwakenedObeliskLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->id();
            $table->integer('source_map_object_id');
            $table->string('source_map_object_class_name');
            // Which type of obelisk we're targeting
            $table->integer('target_map_icon_type_id');
            // And for which seasonal index it is (so that we can uniquely identify it)
            $table->integer('target_map_icon_seasonal_index')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_object_to_awakened_obelisk_links');
    }
}
