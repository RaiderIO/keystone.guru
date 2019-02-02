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
            $table->dropColumn('randomcolumn');
            $table->boolean('active')->default(true);
        });

        // Re-seed the affixes
        Artisan::call('db:seed', array('--class' => 'AffixSeeder', '--database' => 'migrate', '--force' => true));
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
