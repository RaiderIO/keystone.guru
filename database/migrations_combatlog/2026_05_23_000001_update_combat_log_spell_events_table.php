<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_spell_events', function (Blueprint $table) {
            $table->dropColumn(['before', 'after']);
            // Nullable because spell_created events have no specific property
            $table->string('property')->nullable()->after('event_type');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_spell_events', function (Blueprint $table) {
            $table->dropColumn('property');
            $table->json('before')->nullable()->after('event_type');
            $table->json('after')->after('before');
        });
    }
};
