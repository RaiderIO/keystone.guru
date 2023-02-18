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
            // Temp to prevent it from being overridden when 99 and 100 get migrated
            101 => 990,
            102 => 1000,
            103 => 101,
            // These were deleted in previous migration
//            104 => false,
//            105 => false,
            106 => 102,
            107 => 103,
            108 => 104,
            109 => 105,
            110 => 106,
            99  => 107,
            100 => 108,
            // Correct wrong ones to the correct ones
            990 => 99,
            1000 => 100,
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
