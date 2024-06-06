<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update('
            UPDATE `floors` SET `enemy_engagement_max_range` = null WHERE `enemy_engagement_max_range` = 150;
        ');
        DB::update('
            UPDATE `floors` SET `enemy_engagement_max_range_patrols` = null WHERE `enemy_engagement_max_range_patrols` = 50;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::update('
            UPDATE `floors` SET `enemy_engagement_max_range` = 150 WHERE `enemy_engagement_max_range` is null;
        ');
        DB::update('
            UPDATE `floors` SET `enemy_engagement_max_range_patrols` = 50 WHERE `enemy_engagement_max_range_patrols` is null;
        ');
    }
};
