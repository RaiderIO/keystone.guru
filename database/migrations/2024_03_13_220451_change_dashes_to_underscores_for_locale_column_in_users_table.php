<?php /** @noinspection SqlResolve */

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update('
            UPDATE `users` SET `locale` = REPLACE(`locale`, "-", "_")
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::update('
            UPDATE `users` SET `locale` = REPLACE(`locale`, "_", "-")
        ');
    }
};
