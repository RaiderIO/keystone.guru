<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::insert(
            '
            INSERT INTO `roles`
                (name, display_name, description, created_at, updated_at)
            VALUES
                ("internal_team", "Internal Team", "Internal Team", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::delete('DELETE FROM `roles` WHERE name = "internal_team"');
    }
};
