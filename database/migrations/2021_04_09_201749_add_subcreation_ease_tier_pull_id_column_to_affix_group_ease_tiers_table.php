<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubcreationEaseTierPullIdColumnToAffixGroupEaseTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_group_ease_tiers', function (Blueprint $table) {
            $table->integer('subcreation_ease_tier_pull_id')->after('id');

            $table->index('subcreation_ease_tier_pull_id');
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
            $table->dropColumn('subcreation_ease_tier_pull_id');

            $table->dropIndex('affix_group_ease_tiers_subcreation_ease_tier_pull_id_index');
        });
    }
}
