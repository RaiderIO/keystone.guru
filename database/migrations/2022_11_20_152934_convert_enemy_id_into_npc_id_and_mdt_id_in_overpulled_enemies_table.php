<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ConvertEnemyIdIntoNpcIdAndMdtIdInOverpulledEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::delete('
            DELETE `overpulled_enemies`.*
            FROM `overpulled_enemies`
                 LEFT JOIN `enemies` ON `enemies`.`id` = `overpulled_enemies`.`enemy_id`
            WHERE `enemies`.`id` is null;
        ');

        DB::update('
            UPDATE `overpulled_enemies`
                LEFT JOIN `enemies` ON `enemies`.`id` = `overpulled_enemies`.`enemy_id`
            SET `overpulled_enemies`.`npc_id` = coalesce(`enemies`.`mdt_npc_id`, `enemies`.`npc_id`), `overpulled_enemies`.`mdt_id` = `enemies`.`mdt_id`
                WHERE `enemies`.`mdt_id` is not null;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::update('
            UPDATE `overpulled_enemies`
                SET `overpulled_enemies`.`npc_id` = null, `overpulled_enemies`.`mdt_id` = null;
        ');
    }
}
