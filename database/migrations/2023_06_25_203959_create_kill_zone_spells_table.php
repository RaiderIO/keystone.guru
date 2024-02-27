<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('kill_zone_spells', function (Blueprint $table) {
            $table->id();
            $table->integer('kill_zone_id');
            $table->integer('spell_id');

            $table->index(['kill_zone_id']);
            $table->index(['spell_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('kill_zone_spells');
    }
};
