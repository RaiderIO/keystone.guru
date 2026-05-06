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
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->integer('affix_group_id')->after('id');

            $table->index(['affix_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->dropColumn('affix_group_id');
        });
    }
};
