<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_session_combat_log_batches', static function (Blueprint $table) {
            $table->id();
            $table->integer('live_session_id');
            $table->unsignedBigInteger('sequence')->nullable();
            $table->longText('lines')->charset('binary')->nullable(); // LONGBLOB — gzip-compressed raw combat-log lines
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['live_session_id', 'sequence'], 'ls_cl_batches_session_sequence_unique');
            $table->index(['live_session_id', 'consumed_at'], 'ls_cl_batches_session_consumed_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_combat_log_batches');
    }
};
