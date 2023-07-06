<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->integer('model_id')->nullable();
            $table->string('model_class')->nullable();
            $table->integer('category');
            $table->string('tag');
            $table->integer('value');
            $table->timestamps();

            $table->index(['model_id', 'model_class']);
            $table->index(['category', 'tag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metrics');
    }
}
