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
        Schema::create('user_ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('count');
            $table->string('ip_address');
            $table->timestamps();

            $table->index(['user_id', 'ip_address']);
            $table->index(['ip_address']);
            $table->unique(['user_id', 'ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ip_addresses');
    }
};
