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
        Schema::create('user_social_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->string('platform', 32);
            $table->string('url');
            $table->timestamps();

            // A user may only link a given platform once
            $table->unique(['user_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_links');
    }
};
