<?php

use App\Models\PublishedState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Routes left behind in a team-published state by a team removal that only nulled team_id.
        // Such a route satisfies no membership check and is invisible to everyone, its author
        // included, so put it back in the only state it can be reached from.
        DB::table('dungeon_routes')
            ->whereNull('team_id')
            ->where('published_state_id', PublishedState::ALL[PublishedState::TEAM])
            ->update(['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The routes this touched are indistinguishable from routes that were always unpublished
    }
};
