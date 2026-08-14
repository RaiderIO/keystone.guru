<?php

use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The register form's placeholder used to submit -1, which was written to
     * users.game_server_region_id verbatim (#4003) - a dangling reference that feeds
     * reset-day/epoch logic. Point those rows at the same default a no-region
     * registration now gets.
     */
    public function up(): void
    {
        User::where('game_server_region_id', -1)
            ->update(['game_server_region_id' => GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION]]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to restore - the -1 values were corrupt data
    }
};
