<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tag_models', function (Blueprint $table)
        {
            $table->id();
            $table->integer('user_id');
            $table->integer('tag_id');
            $table->integer('model_id');
            $table->string('model_class');
            $table->string('color');

            $table->index(['user_id']);
            $table->index(['tag_id']);
            $table->index(['model_id', 'model_class']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tag_models');
    }
}
