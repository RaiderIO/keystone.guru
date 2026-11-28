<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * @var array<string, array{column: string, length: int}> Table name => the legacy identifier column that `key` replaced
     */
    private const array LEGACY_COLUMNS = [
        'expansions'                          => ['column' => 'shortname', 'length' => 255],
        'game_server_regions'                 => ['column' => 'short', 'length' => 255],
        'tag_categories'                      => ['column' => 'name', 'length' => 255],
        'dungeon_route_collection_categories' => ['column' => 'name', 'length' => 64],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::LEGACY_COLUMNS as $tableName => ['column' => $column, 'length' => $length]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $length) {
                $table->string($column, $length)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::LEGACY_COLUMNS as $tableName => ['column' => $column, 'length' => $length]) {
            DB::table($tableName)->whereNull($column)->update([$column => DB::raw('`key`')]);

            Schema::table($tableName, function (Blueprint $table) use ($column, $length) {
                $table->string($column, $length)->nullable(false)->change();
            });
        }
    }
};
