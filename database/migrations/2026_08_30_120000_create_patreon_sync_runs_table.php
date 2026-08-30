<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per `patreon:refreshmembers` run (#4373).
     *
     * The hourly sync's outcome was previously observable only through log lines below `error` level,
     * which go to stderr and a daily file inside the container - unreadable from anywhere else and gone
     * on the next deploy. Persisting the shape of each run is what makes "did this run fetch the whole
     * campaign?" answerable after the fact: a run that fetched markedly fewer members than the one before
     * it is a truncated fetch, and every member it never saw silently kept whatever benefits they had.
     *
     * At 24 rows a day this needs no retention sweep.
     */
    public function up(): void
    {
        Schema::create('patreon_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();

            // How much of the campaign arrived. `truncated` is set when pagination gave up early - the
            // run then fails, but the row is what shows how far it got.
            $table->unsignedInteger('pages_fetched')->default(0);
            $table->unsignedInteger('members_fetched')->default(0);
            $table->boolean('truncated')->default(false);

            // Per-outcome tallies, one column per ApplyPaidBenefitsForMemberResult case plus the members
            // whose application threw
            $table->unsignedInteger('members_applied')->default(0);
            $table->unsignedInteger('members_not_linked')->default(0);
            $table->unsignedInteger('members_unknown_benefits')->default(0);
            $table->unsignedInteger('members_unknown_tiers')->default(0);
            $table->unsignedInteger('members_failed')->default(0);

            // Whether the command exited zero. A successful run that fetched half the campaign is the
            // exact combination this table exists to make visible.
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
