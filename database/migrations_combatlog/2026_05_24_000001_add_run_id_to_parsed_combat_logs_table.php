<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parsed_combat_logs', function (Blueprint $table) {
            $table->bigInteger('run_id')->nullable()->unique()->after('combat_log_path');
        });
    }

    public function down(): void
    {
        Schema::table('parsed_combat_logs', function (Blueprint $table) {
            $table->dropColumn('run_id');
        });
    }
};
