<?php

use Illuminate\Database\Migrations\Migration;

class DeleteUnknownAffixGroupsForDragonflightS1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::delete('
            DELETE FROM `dungeon_route_affix_groups` WHERE affix_group_id IN (104, 105)
            ');


        DB::delete('
            DELETE FROM `affix_group_ease_tiers` WHERE affix_group_id IN (104, 105)
            ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
