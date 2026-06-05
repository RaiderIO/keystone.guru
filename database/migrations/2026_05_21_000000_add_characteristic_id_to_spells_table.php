<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->unsignedBigInteger('characteristic_id')->nullable()->after('hidden_on_map');
        });
    }

    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn('characteristic_id');
        });
    }
};
