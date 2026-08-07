<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'combatlog';

    public function up(): void
    {
        Schema::dropIfExists('combat_log_parse_failures');
    }

    public function down(): void
    {
        Schema::create('combat_log_parse_failures', static function (Blueprint $table): void {
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
};
