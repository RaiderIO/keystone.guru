<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('challenge_mode_run_data', function (Blueprint $table) {
            $table->mediumText('post_body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('challenge_mode_run_data', function (Blueprint $table) {
            $table->mediumText('post_body')->nullable(false)->change();
        });
    }
};
