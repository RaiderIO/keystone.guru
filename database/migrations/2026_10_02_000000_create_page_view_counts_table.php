<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_view_counts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model_class');
            $table->integer('model_id');
            // page_views.source, with null stored as 0 so the unique key below can match it.
            $table->unsignedTinyInteger('source')->default(0);
            $table->date('viewed_on');
            $table->unsignedInteger('views');

            $table->unique(['model_class', 'model_id', 'viewed_on', 'source'], 'page_view_counts_model_day_source_unique');
            $table->index('viewed_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_view_counts');
    }
};
