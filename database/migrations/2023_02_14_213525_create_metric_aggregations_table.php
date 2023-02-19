<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetricAggregationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metric_aggregations', function (Blueprint $table) {
            $table->integer('model_id')->nullable();
            $table->string('model_class')->nullable();
            $table->integer('category');
            $table->string('tag');
            $table->integer('value');
            $table->timestamps();

            $table->primary(['model_id', 'model_class', 'category', 'tag']);

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
        Schema::dropIfExists('metric_aggregations');
    }
}
