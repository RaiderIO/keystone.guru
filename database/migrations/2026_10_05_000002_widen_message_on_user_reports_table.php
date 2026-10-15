<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * A report's message is validated up to 1000 characters. Both lengths need a two-byte length prefix, so widening
     * the VARCHAR changes only the table's metadata.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `user_reports` MODIFY `message` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE `user_reports` SET `message` = LEFT(`message`, 255) WHERE CHAR_LENGTH(`message`) > 255');
        DB::statement('ALTER TABLE `user_reports` MODIFY `message` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }
};
