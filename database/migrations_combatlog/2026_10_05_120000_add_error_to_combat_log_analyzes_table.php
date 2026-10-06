<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_analyzes', function (Blueprint $table) {
            $table->text('error')->nullable()->after('status_string');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_analyzes', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};
