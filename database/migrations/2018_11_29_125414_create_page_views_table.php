<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePageViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('model_id');
            $table->string('model_class');
            $table->string('session_id');

            $table->index('user_id');
            $table->index('model_id');
            $table->index('model_class');
            // Speed up searching when searching views for a specific model
            $table->index(['model_id', 'model_class']);
            // Speed up searching when finding a very specific page view
            $table->index(['user_id', 'model_id', 'model_class', 'session_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page_views');
    }
}
