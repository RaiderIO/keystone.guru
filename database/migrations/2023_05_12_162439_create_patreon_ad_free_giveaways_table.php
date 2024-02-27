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
        Schema::create('patreon_ad_free_giveaways', function (Blueprint $table) {
            $table->id();
            $table->integer('giver_user_id');
            $table->integer('receiver_user_id');
            $table->timestamps();

            $table->index(['giver_user_id']);
            $table->index(['receiver_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patreon_ad_free_giveaways');
    }
};
