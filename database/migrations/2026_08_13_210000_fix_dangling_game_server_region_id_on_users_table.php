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
        // toBase() drops to the query builder on purpose: the Eloquent builder's update() appends
        // updated_at = now(), which would rewrite the timestamp of every affected user (-1 was the
        // register form's placeholder default, so that is a large slice of the table) for a
        // backfill that down() deliberately cannot undo
        User::where('game_server_region_id', -1)
            ->toBase()
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
