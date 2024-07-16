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
        Schema::create('npc_spells', function (Blueprint $table) {
            $table->id();
            $table->integer('npc_id');
            $table->integer('spell_id');

            $table->index(['npc_id']);
            $table->index(['spell_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('npc_spells');
    }
};
