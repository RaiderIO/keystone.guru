<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->string('tiers_hash')->after('current_affixes');
            $table->index(['tiers_hash']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->dropColumn('tiers_hash');
        });
    }
};
