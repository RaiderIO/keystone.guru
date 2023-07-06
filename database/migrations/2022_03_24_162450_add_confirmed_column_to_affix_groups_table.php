<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfirmedColumnToAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_groups', function (Blueprint $table) {
            $table->boolean('confirmed')->after('seasonal_index')->default(1);
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
            $table->dropColumn('confirmed');
        });
    }
}
