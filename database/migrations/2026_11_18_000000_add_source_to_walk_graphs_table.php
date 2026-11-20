<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('walk_graphs', function (Blueprint $table) {
            $table->string('source', 16)->default('combat_log')->after('ui_map_id');
        });
    }

    public function down(): void
    {
        Schema::table('walk_graphs', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
