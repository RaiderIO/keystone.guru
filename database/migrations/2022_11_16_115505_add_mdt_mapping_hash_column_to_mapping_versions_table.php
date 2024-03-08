<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->string('mdt_mapping_hash')->nullable()->default(null)->after('version');
            $table->index(['mdt_mapping_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->dropIndex(['mdt_mapping_hash']);
            $table->dropColumn('mdt_mapping_hash');
        });
    }
};
