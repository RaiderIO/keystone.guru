<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('character_classes', static function (Blueprint $table): void {
            $table->string('slug')->default('')->after('key');
            $table->index('slug');
        });

        // Backfill the url-friendly slug from the existing underscored key, e.g. death_knight -> death-knight.
        DB::statement("UPDATE character_classes SET slug = REPLACE(`key`, '_', '-')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_classes', static function (Blueprint $table): void {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
