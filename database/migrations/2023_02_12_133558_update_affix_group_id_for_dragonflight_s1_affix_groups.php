<?php

use Illuminate\Database\Migrations\Migration;

class UpdateAffixGroupIdForDragonflightS1AffixGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $mapping = [
            99  => 107,
            100 => 108,
            101 => 99,
            // These were deleted in previous migration
//            102 => false,
//            103 => false,
            104 => 100,
            105 => 101,
            106 => 102,
            107 => 103,
            108 => 104,
            109 => 105,
            110 => 106,
        ];

        foreach ($mapping as $old => $new) {
            DB::update('
            UPDATE `dungeon_route_affix_groups` SET affix_group_id = :new WHERE affix_group_id = :old
            ', ['new' => $new, 'old' => $old]);

            DB::update('
            UPDATE `affix_group_ease_tiers` SET affix_group_id = :new WHERE affix_group_id = :old
            ', ['new' => $new, 'old' => $old]);
        }
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
