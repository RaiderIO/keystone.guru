<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expansions', function (Blueprint $table) {
            $table->string('key')->nullable()->after('id')->index('expansions_key_index');
        });

        DB::table('expansions')->update(['key' => DB::raw('`shortname`')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expansions', function (Blueprint $table) {
            $table->dropIndex('expansions_key_index');
            $table->dropColumn('key');
        });
    }
};
