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
        Schema::create('combat_log_analyzes', function (Blueprint $table) {
            $table->id();
            $table->string('combat_log_path');
            $table->integer('percent_completed')->nullable();
            $table->integer('status')->default(0);
            $table->string('status_string')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_log_analyzes');
    }
};
