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
        Schema::table('patreon_data', function (Blueprint $table) {
            $table->string('scope')->after('email');
            $table->string('version')->after('refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patreon_data', function (Blueprint $table) {
            $table->dropColumn('scope');
            $table->dropColumn('version');
        });
    }
};
