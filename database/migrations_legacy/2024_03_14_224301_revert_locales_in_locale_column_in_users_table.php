<?php /** @noinspection SqlResolve */

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapping = [
            ['source' => 'de', 'target' => 'de_DE'],
            ['source' => 'en', 'target' => 'en_US'],
        ];

        foreach ($mapping as $bindings) {
            DB::update('
                UPDATE `users` SET `locale` = :target WHERE `locale` = :source
            ', $bindings);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $mapping = [
            ['source' => 'de_DE', 'target' => 'de'],
            ['source' => 'en_US', 'target' => 'en'],
        ];

        foreach ($mapping as $bindings) {
            DB::update('
                UPDATE `users` SET `locale` = :target WHERE `locale` = :source
            ', $bindings);
        }
    }
};
