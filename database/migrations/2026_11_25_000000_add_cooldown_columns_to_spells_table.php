<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->unsignedInteger('cooldown_ms')->nullable()->after('duration');
            $table->unsignedTinyInteger('charges')->nullable()->after('cooldown_ms');
            $table->unsignedInteger('charge_cooldown_ms')->nullable()->after('charges');
            $table->string('cooldown_source', 16)->nullable()->after('charge_cooldown_ms');
        });
    }

    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn(['cooldown_ms', 'charges', 'charge_cooldown_ms', 'cooldown_source']);
        });
    }
};
