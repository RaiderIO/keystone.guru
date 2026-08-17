<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parsed_combat_logs', function (Blueprint $table) {
            // combatlog:pruneparsedlogs deletes in created_at-ordered batches (#4062) - without this the
            // table has no index usable for that filter and every batch is a full scan.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('parsed_combat_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
