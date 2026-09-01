<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patreon_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();

            $table->unsignedInteger('pages_fetched')->default(0);
            $table->unsignedInteger('members_fetched')->default(0);
            $table->boolean('truncated')->default(false);

            // One column per ApplyPaidBenefitsForMemberResult case, plus the members whose application threw
            $table->unsignedInteger('members_applied')->default(0);
            $table->unsignedInteger('members_not_linked')->default(0);
            $table->unsignedInteger('members_unknown_benefits')->default(0);
            $table->unsignedInteger('members_unknown_tiers')->default(0);
            $table->unsignedInteger('members_failed')->default(0);

            $table->boolean('successful')->default(false);
            $table->string('failure_reason')->nullable();

            $table->index('started_at', 'patreon_sync_runs_started_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patreon_sync_runs');
    }
};
