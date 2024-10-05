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
        set_time_limit(999999);

        Schema::table('page_views', function (Blueprint $table) {
            if (Schema::hasIndex('page_views', 'page_views_popularity_index')) {
                $table->dropIndex('page_views_model_class_index');
            }

            if (!Schema::hasIndex('page_views', 'page_views_popularity_index')) {
                $table->index(['model_class', 'created_at', 'model_id'], 'page_views_popularity_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        set_time_limit(999999);

        Schema::table('page_views', function (Blueprint $table) {
            if (Schema::hasIndex('page_views', 'page_views_popularity_index')) {
                $table->dropIndex('page_views_popularity_index');
            }

            if (!Schema::hasIndex('page_views', 'page_views_model_class_index')) {
                $table->index('page_views_model_class_index');
            }
        });
    }
};
