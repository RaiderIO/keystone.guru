<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->dropColumn('source_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->string('source_url')->after('id');
        });
    }
};
