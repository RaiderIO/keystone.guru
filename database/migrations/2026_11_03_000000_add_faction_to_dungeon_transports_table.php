<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_transports', function (Blueprint $table) {
            $table->enum('faction', ['any', 'alliance', 'horde'])->default('any')->after('link_key');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_transports', function (Blueprint $table) {
            $table->dropColumn('faction');
        });
    }
};
