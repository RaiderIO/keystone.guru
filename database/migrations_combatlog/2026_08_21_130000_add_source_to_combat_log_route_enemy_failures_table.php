<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a failure row came from (#4222): null for rows this environment recorded itself, or the remote host key
 * (production, staging) for rows combatlog:importenemyfailures pulled in - needed to know which deployment to ask
 * for the route's post body later. Nullable + no default-change, so it is backward-compatible with running code.
 */
return new class extends Migration {
    protected $connection = 'combatlog';

    public function up(): void
    {
        Schema::connection('combatlog')->table('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('dungeon_route_id');
        });
    }

    public function down(): void
    {
        Schema::connection('combatlog')->table('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
