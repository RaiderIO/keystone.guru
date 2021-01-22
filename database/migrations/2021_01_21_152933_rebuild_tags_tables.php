<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RebuildTagsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('tag_models');

        Schema::create('tags', function (Blueprint $table)
        {
            $table->id();
            $table->integer('user_id');
            $table->integer('tag_category_id');
            $table->integer('model_id')->nullable(true);
            $table->string('model_class')->nullable(true);
            $table->string('name');
            $table->string('color')->nullable(true);
            $table->timestamps();

            $table->index('user_id');
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
        // We're effed
    }
}
