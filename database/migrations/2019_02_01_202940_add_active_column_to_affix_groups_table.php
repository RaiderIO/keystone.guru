<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveColumnToAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_groups', function (Blueprint $table) {
            // May already have the column, I had to adjust the initial database creation table otherwise the seeder
            // couldn't run. Clean run this won't run, when really migrating this will run
            if (!Schema::hasColumn('affix_groups', 'active')) {
                $table->dropColumn('randomcolumn');
                $table->boolean('active')->default(true);
            }
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
            $table->removeColumn('active');
            $table->integer('randomcolumn');
        });
    }
}
