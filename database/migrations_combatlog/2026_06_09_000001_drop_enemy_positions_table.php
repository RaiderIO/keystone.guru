<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'combatlog';

    public function up(): void
    {
        Schema::dropIfExists('enemy_positions');
    }

    public function down(): void
    {
        Schema::create('enemy_positions', static function (Blueprint $table): void {
            $table->id();
            $table->integer('challenge_mode_run_id')->index();
            $table->string('guid')->unique();
            $table->integer('floor_id');
            $table->integer('npc_id');
            $table->double('lat');
            $table->double('lng');
            $table->timestamp('created_at')->index();

            $table->index(['floor_id', 'npc_id']);
            $table->index(['lat', 'lng']);
        });
    }
};
