<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_session_obsolete_enemies', static function (Blueprint $table) {
            $table->id();
            $table->integer('live_session_id');
            $table->integer('npc_id');
            $table->integer('mdt_id');

            $table->unique(['live_session_id', 'npc_id', 'mdt_id'], 'ls_obsolete_enemies_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_obsolete_enemies');
    }
};
