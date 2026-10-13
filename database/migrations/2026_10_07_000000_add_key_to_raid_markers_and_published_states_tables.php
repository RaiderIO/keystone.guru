<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * @var array<string, string> Table name => index name
     */
    private const array TABLES = [
        'raid_markers'     => 'raid_markers_key_index',
        'published_states' => 'published_states_key_index',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->string('key')->nullable()->after('id')->index($indexName);
            });

            DB::table($tableName)->update(['key' => DB::raw('`name`')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
                $table->dropColumn('key');
            });
        }
    }
};
