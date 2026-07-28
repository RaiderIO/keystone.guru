<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_session_player_positions', static function (Blueprint $table) {
            $table->id();
            $table->integer('live_session_id');
            $table->string('player_guid');
            $table->string('character_name');
            $table->double('lat');
            $table->double('lng');
            $table->integer('floor_id');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['live_session_id', 'player_guid'], 'ls_player_positions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_player_positions');
    }
};
