<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Map ID 0 is a real map in the game (Eastern Kingdoms); every other dungeon at 0 or below was defaulted.
     */
    private const string GENUINE_MAP_ID_ZERO_KEY = 'eastern_kingdoms';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeons', static function (Blueprint $table) {
            $table->integer('map_id')->nullable()->default(null)->change();
        });

        DB::table('dungeons')
            ->where(static function (Builder $query) {
                $query->where('map_id', '<', 0)
                    ->orWhere(static function (Builder $query) {
                        $query->where('map_id', 0)
                            ->where('key', '!=', self::GENUINE_MAP_ID_ZERO_KEY);
                    });
            })
            ->update(['map_id' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('dungeons')->whereNull('map_id')->update(['map_id' => 0]);

        Schema::table('dungeons', static function (Blueprint $table) {
            $table->integer('map_id')->default(0)->change();
        });
    }
};
