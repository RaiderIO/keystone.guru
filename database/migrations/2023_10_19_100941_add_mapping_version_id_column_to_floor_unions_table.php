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
        Schema::table('floor_unions', function (Blueprint $table) {
            $table->integer('mapping_version_id')->after('id');

            $table->index(['mapping_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('floor_unions', function (Blueprint $table) {
            $table->dropColumn('mapping_version_id');
        });
    }
};
