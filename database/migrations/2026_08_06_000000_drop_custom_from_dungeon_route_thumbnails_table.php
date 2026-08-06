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
        Schema::table('dungeon_route_thumbnails', static function (Blueprint $table): void {
            $table->dropIndex('dungeon_route_thumbnails_custom_index');
            $table->dropColumn('custom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_route_thumbnails', static function (Blueprint $table): void {
            $table->boolean('custom')->default(false)->after('file_id')->index();
        });
    }
};
