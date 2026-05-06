<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parsed_combat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('combat_log_path');
            $table->boolean('extracted_data')->default(false);
            $table->timestamps();

            $table->index('combat_log_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parsed_combat_logs');
    }
};
