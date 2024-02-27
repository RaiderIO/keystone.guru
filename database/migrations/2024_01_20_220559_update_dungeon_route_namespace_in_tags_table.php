<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::update('UPDATE `tags` SET `model_class` = "App\Models\DungeonRoute\DungeonRoute" WHERE `model_class` = "App\Models\DungeonRoute"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::update('UPDATE `tags` SET `model_class` = "App\Models\DungeonRoute" WHERE `model_class` = "App\Models\DungeonRoute\DungeonRoute"');
    }
};
