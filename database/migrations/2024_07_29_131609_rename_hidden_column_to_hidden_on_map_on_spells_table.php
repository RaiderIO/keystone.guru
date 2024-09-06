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
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn('hidden');
            $table->boolean( 'hidden_on_map')->default(false);

            $table->index(['hidden_on_map']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn('hidden_on_map');
            $table->boolean( 'hidden')->default(false);

            $table->index(['hidden']);
        });
    }
};
