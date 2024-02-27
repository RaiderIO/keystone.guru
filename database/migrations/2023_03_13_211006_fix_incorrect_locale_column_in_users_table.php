<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update('UPDATE `users` SET `locale` = "en-US" WHERE `locale` = "en"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No going back
    }
};
