<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patreon_manual_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            // Nullable because a grant may outlive the admin account that issued it, and because the
            // rows backfilled for grants issued before this table existed have no known granter.
            $table->unsignedInteger('granted_by_user_id')->nullable();
            $table->string('reason');
            // A revoked grant is kept rather than deleted - the point of this table is that the
            // history of who was given benefits, and why, survives the cleanup.
            $table->timestamp('revoked_at')->nullable()->index();
            $table->unsignedInteger('revoked_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patreon_manual_grants');
    }
};
