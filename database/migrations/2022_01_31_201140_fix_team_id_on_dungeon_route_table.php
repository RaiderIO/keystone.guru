<?php

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('team_id')->nullable(true)->change();
        });
        DungeonRoute::query()->where('team_id', '<=', 0)->update(['team_id' => null]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DungeonRoute::query()->whereNull('team_id')->update(['team_id' => -1]);
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('team_id')->nullable(false)->change();
        });
    }
};
