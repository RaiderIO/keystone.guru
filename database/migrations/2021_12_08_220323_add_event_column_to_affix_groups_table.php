<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventColumnToAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_groups', function (Blueprint $table) {
            $table->string('event')->nullable()->default(null)->after('seasonal_index');
            $table->index('event');
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
            $table->dropColumn('event');
        });
    }
}
