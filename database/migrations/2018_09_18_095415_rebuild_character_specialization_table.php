<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RebuildCharacterSpecializationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('character_class_specializations');
        Schema::create('character_class_specializations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('character_class_id');
            $table->string('name');
            $table->string('icon_file_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Old table, but it'll be truncated
        Schema::create('character_class_specializations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('class_id');
            $table->string('name');
            $table->string('icon_file_id');
        });
    }
}
