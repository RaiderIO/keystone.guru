<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_group_ease_tier_pulls', function (Blueprint $table) {
            $table->dropColumn('source_url');
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
            $table->string('source_url')->after('id');
        });
    }
};
