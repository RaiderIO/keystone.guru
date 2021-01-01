<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEchoColorsForNewUsersFromOAuthProviders extends Migration
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
