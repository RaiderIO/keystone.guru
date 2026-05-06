<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapping = [
            ['source' => '3', 'target' => '20'],
            ['source' => '2', 'target' => '30'],
            ['source' => '20', 'target' => '2'],
            ['source' => '30', 'target' => '3'],
        ];

        foreach ($mapping as $bindings) {
            DB::update('
                UPDATE `users` SET `game_version_id` = :target WHERE `game_version_id` = :source
            ', $bindings);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Oddly enough, down migration is equal to up migration
        $mapping = [
            ['source' => '3', 'target' => '20'],
            ['source' => '2', 'target' => '30'],
            ['source' => '20', 'target' => '2'],
            ['source' => '30', 'target' => '3'],
        ];

        foreach ($mapping as $bindings) {
            DB::update('
                UPDATE `users` SET `game_version_id` = :target WHERE `game_version_id` = :source
            ', $bindings);
        }
    }
};
