<?php

use App\Models\GameServerRegion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * users.game_server_region_id defaulted to -1, a region id that does not exist. Every insert path
     * that omits the column - LaratrustSeeder, the user factory and the three OAuth login controllers -
     * therefore produced exactly the dangling reference the 2026_08_13 repair migration was written to
     * clean up (#4502). Default the column to the same region a no-region registration gets, and sweep
     * whatever accumulated since that repair ran.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('game_server_region_id')
                ->default(GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION])
                ->change();
        });

        // Query builder rather than Eloquent, as in the 2026_08_13 repair: the Eloquent builder's
        // update() appends updated_at = now(), rewriting the timestamp of every affected user for a
        // backfill that down() deliberately cannot undo
        DB::table('users')
            ->where('game_server_region_id', -1)
            ->update(['game_server_region_id' => GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION]]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only the default is restored - the -1 values were corrupt data
        Schema::table('users', function (Blueprint $table) {
            $table->integer('game_server_region_id')->default(-1)->change();
        });
    }
};
