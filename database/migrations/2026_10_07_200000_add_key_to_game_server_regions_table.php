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
        Schema::table('game_server_regions', function (Blueprint $table) {
            $table->string('key')->nullable()->after('id')->index('game_server_regions_key_index');
        });

        DB::table('game_server_regions')->update(['key' => DB::raw('`short`')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_server_regions', function (Blueprint $table) {
            $table->dropIndex('game_server_regions_key_index');
            $table->dropColumn('key');
        });
    }
};
