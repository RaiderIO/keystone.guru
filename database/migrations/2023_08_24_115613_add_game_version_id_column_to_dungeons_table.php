<?php

use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->integer('game_version_id')
                ->after('expansion_id')
                ->default(GameVersion::ALL[GameVersion::DEFAULT_GAME_VERSION]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('game_version_id');
        });
    }
};
