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
        Schema::table('npc_classifications', function (Blueprint $table) {
            $table->renameColumn('shortname', 'key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npc_classifications', function (Blueprint $table) {
            $table->renameColumn('key', 'shortname');
        });
    }
};
