<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('npc_characteristics', function (Blueprint $table) {
            $table->unique(['npc_id', 'characteristic_id'], 'npc_char_npc_char_unique');
        });
    }

    public function down(): void
    {
        Schema::table('npc_characteristics', function (Blueprint $table) {
            $table->dropUnique('npc_char_npc_char_unique');
        });
    }
};
