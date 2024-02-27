<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_aggregations');
    }
};
