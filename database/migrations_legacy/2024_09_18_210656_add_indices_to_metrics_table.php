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
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropIndex(['model_id', 'model_class']);
            $table->index(['model_id', 'model_class', 'category', 'tag'], 'compound');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropIndex('compound');
            $table->index(['model_id', 'model_class']);
        });
    }
};
