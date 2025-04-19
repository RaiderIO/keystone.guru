<?php

use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->integer('game_version_id')->default(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])->after('id');
            $table->index('game_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropIndex(['game_version_id']);
            $table->dropColumn('game_version_id');
        });
    }
};
