<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_session_combat_log_buffers', static function (Blueprint $table) {
            $table->id();
            $table->integer('live_session_id');
            $table->longText('buffer')->charset('binary')->nullable(); // LONGBLOB — gzip-compressed raw combat-log lines
            $table->integer('last_sequence')->nullable();
            $table->timestamps();

            $table->unique('live_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_combat_log_buffers');
    }
};
