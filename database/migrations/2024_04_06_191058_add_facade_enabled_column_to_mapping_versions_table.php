<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->boolean('facade_enabled')->after('mdt_mapping_hash')->default(false);

            $table->index(['facade_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->dropColumn('facade_enabled');
        });
    }
};
