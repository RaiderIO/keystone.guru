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
        Schema::table('release_changelog_categories', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->string('key')->after('id');
            $table->string('name')->after('key');

            $table->index(['key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('release_changelog_categories', function (Blueprint $table) {
            $table->string('category')->after('id');
            $table->dropColumn('key');
            $table->dropColumn('name');

            $table->dropIndex(['key']);
        });
    }
};
