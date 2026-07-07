<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_parse_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id')->index();
            $table->unsignedInteger('season_id')->nullable();
            $table->unsignedBigInteger('combat_log_version')->nullable();
            $table->unsignedInteger('line_number')->nullable();
            $table->text('raw_line')->nullable();
            $table->text('message');
            $table->string('exception_class');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_parse_failures');
    }
};
