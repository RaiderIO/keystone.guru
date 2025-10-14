<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Work around SQLSTATE[HY000]: General error: 1709 Index column size too large
        // is caused by trying to create an index on a VARCHAR(255) column using the utf8mb4 character set.
        // Since each character can be up to 4 bytes, 255 × 4 = 1020 bytes, which exceeds the 767-byte limit for
        // indexed columns in certain MySQL row formats.
        DB::statement('ALTER TABLE metrics MODIFY model_class VARCHAR(191);');
        DB::statement('ALTER TABLE metrics MODIFY tag VARCHAR(191);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE metrics MODIFY model_class VARCHAR(255);');
        DB::statement('ALTER TABLE metrics MODIFY tag VARCHAR(255);');
    }
};
