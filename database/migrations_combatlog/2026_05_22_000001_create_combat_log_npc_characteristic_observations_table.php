<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_npc_characteristic_observations', function (Blueprint $table) {
            $table->id();
            $table->integer('npc_id');
            $table->integer('characteristic_id');
            $table->date('observed_on');
            $table->string('combat_log_path');
            $table->timestamps();

            $table->unique(['npc_id', 'characteristic_id', 'observed_on'], 'clnco_npc_char_date_unique');
            $table->index(['npc_id', 'characteristic_id', 'observed_on'], 'clnco_npc_char_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_npc_characteristic_observations');
    }
};
