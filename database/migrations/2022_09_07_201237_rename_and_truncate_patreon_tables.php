<?php

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAndTruncatePatreonTables extends Migration
{
    /**
     * This is after re-doing the patreon integration. To strip everyone of (no longer paid for) benefits, we get rid of
     * all the data and ask everyone to re-link their accounts with Patreon.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('paid_tiers', 'patreon_benefits');
        Schema::rename('patreon_data', 'patreon_user_links');
        Schema::rename('patreon_tiers', 'patreon_user_benefits');

        PatreonBenefit::truncate();
        PatreonUserLink::truncate();
        PatreonUserBenefit::truncate();

        Schema::table('users', function (Blueprint $table) {
            // Get rid of old column - we also want to destroy the data inside the column
            $table->dropIndex('users_patreon_data_id_index');
            $table->dropColumn('patreon_data_id');

            // Add the new column with a new index
            $table->integer('patreon_user_link_id')->nullable()->default(null)->after('game_server_region_id');
            $table->index('patreon_user_link_id');
        });

        Schema::table('patreon_user_benefits', function (Blueprint $table) {
            // Get rid of old column - we also want to destroy the data inside the column
            $table->dropIndex('patreon_tiers_paid_tier_id_index');
            $table->dropIndex('patreon_tiers_patreon_data_id_index');
            $table->dropColumn('patreon_data_id');
            $table->dropColumn('paid_tier_id');

            // Add the new column with a new index
            $table->integer('patreon_user_link_id')->after('id');
            $table->integer('patreon_benefit_id')->after('patreon_user_link_id');
            $table->index('patreon_user_link_id');
            $table->index('patreon_benefit_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('patreon_benefits', 'paid_tiers');
        Schema::rename('patreon_user_links', 'patreon_data');
        Schema::rename('patreon_user_benefits', 'patreon_tiers');
    }
}
