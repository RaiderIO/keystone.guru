<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpansionIdToAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_groups', function (Blueprint $table) {
            $table->integer('expansion_id')->after('season_id');

            $table->index('expansion_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affix_groups', function (Blueprint $table) {
            $table->dropColumn('expansion_id');
        });
    }
}
