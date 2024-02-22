<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAffixGroupEaseTierPullIdColumnToAffixGroupEaseTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_group_ease_tiers', function (Blueprint $table) {
            // These indices are not necessary
            $table->dropIndex(['affix_group_id']);
            $table->dropIndex(['subcreation_ease_tier_pull_id']);
            $table->renameColumn('subcreation_ease_tier_pull_id', 'affix_group_ease_tier_pull_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affix_group_ease_tiers', function (Blueprint $table) {
            $table->renameColumn('affix_group_ease_tier_pull_id', 'subcreation_ease_tier_pull_id');
            $table->index(['subcreation_ease_tier_pull_id']);
            $table->index(['affix_group_id']);
        });
    }
}
