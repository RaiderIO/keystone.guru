<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Class UpdateEchoColorsForNewUsers
 * @author Wouter
 * @since 24/05/2020
 * @see https://github.com/Wotuu/keystone.guru/issues/298
 */
class UpdateEchoColorsForNewUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update('update users SET echo_color = CONCAT(\'#\',LPAD(CONV(ROUND(RAND()*16777215),10,16),6,0)) WHERE echo_color = \'\';');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No matter
    }
}
