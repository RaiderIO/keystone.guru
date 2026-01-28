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
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_name_user_id_tag_category_id_index');
            $table->index([
                'name',
                'context_id',
                'context_class',
                'tag_category_id',
            ], 'tags_name_context_id_class_category_index');

            $table->dropIndex('tags_user_id_index');
            $table->index([
                'context_id',
                'context_class',
            ], 'tags_context_id_context_class_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_context_id_context_class_index');
            $table->index(['user_id'], 'tags_user_id_index');

            $table->dropIndex('tags_name_context_id_class_category_index');
            $table->index([
                'name',
                'user_id',
                'tag_category_id',
            ], 'tags_name_user_id_tag_category_id_index');
        });
    }
};
