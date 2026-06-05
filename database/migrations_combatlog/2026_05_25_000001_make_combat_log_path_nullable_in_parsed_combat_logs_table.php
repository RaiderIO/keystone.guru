<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'combatlog';

    public function up(): void
    {
        Schema::connection('combatlog')->table('parsed_combat_logs', function (Blueprint $table) {
            $table->string('combat_log_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('combatlog')->table('parsed_combat_logs', function (Blueprint $table) {
            $table->string('combat_log_path')->nullable(false)->change();
        });
    }
};
