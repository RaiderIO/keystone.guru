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
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
