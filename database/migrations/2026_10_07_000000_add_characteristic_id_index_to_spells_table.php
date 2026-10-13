<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->index('characteristic_id', 'spells_characteristic_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropIndex('spells_characteristic_id_index');
        });
    }
};
