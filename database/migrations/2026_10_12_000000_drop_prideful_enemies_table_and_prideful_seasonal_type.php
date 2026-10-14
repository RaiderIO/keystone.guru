<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const string SEASONAL_TYPE_WITHOUT_PRIDEFUL
        = "enum('beguiling','awakened','inspiring','tormented','encrypted','mdt_placeholder','requires_activation','shrouded','shrouded_zul_gamux','no_shrouded')";

    private const string SEASONAL_TYPE_WITH_PRIDEFUL
        = "enum('beguiling','awakened','inspiring','prideful','tormented','encrypted','mdt_placeholder','requires_activation','shrouded','shrouded_zul_gamux','no_shrouded')";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A prideful enemy is a regular enemy once the affix is gone, so it falls back to no seasonal type.
        DB::table('enemies')->where('seasonal_type', 'prideful')->update(['seasonal_type' => null]);

        DB::statement(sprintf('ALTER TABLE `enemies` MODIFY `seasonal_type` %s NULL DEFAULT NULL', self::SEASONAL_TYPE_WITHOUT_PRIDEFUL));

        Schema::dropIfExists('prideful_enemies');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(sprintf('ALTER TABLE `enemies` MODIFY `seasonal_type` %s NULL DEFAULT NULL', self::SEASONAL_TYPE_WITH_PRIDEFUL));

        if (!Schema::hasTable('prideful_enemies')) {
            Schema::create('prideful_enemies', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('dungeon_route_id')->index();
                $table->integer('enemy_id')->index();
                $table->integer('floor_id')->index();
                $table->double('lat');
                $table->double('lng');
                $table->timestamps();
            });
        }
    }
};
