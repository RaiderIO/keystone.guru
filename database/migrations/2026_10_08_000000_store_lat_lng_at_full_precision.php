<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** @var array<int, string> Tables whose lat/lng may still be double(8,2), which rounds every coordinate to 2 decimals. */
    private const array TABLES = [
        'dungeon_floor_switch_markers',
        'enemies',
        'floor_unions',
    ];

    /**
     * Before Laravel 11, `double('lat')` created a double(8,2) column. Databases migrated back then still
     * have it for enemies; floor_unions and dungeon_floor_switch_markers have it in the schema dump too.
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->double('lat')->change();
                $table->double('lng')->change();
            });
        }
    }

    /**
     * Not reversed: narrowing the columns again would round every stored coordinate.
     */
    public function down(): void
    {
    }
};
