<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('characteristics', function (Blueprint $table) {
            $table->string('icon_name')->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('characteristics', function (Blueprint $table) {
            $table->dropColumn('icon_name');
        });
    }
};
