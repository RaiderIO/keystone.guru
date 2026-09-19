<?php

use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_route_collections', static function (Blueprint $table): void {
            // Defaulted rather than plain NOT NULL: the code running during the rollout does not set it yet
            $table->unsignedInteger('game_version_id')
                ->default(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
                ->after('dungeon_route_collection_category_id');
            $table->unsignedInteger('season_id')->nullable()->after('game_version_id');

            $table->index('game_version_id');
            $table->index('season_id');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_route_collections', static function (Blueprint $table): void {
            $table->dropIndex(['season_id']);
            $table->dropIndex(['game_version_id']);
            $table->dropColumn(['season_id', 'game_version_id']);
        });
    }
};
