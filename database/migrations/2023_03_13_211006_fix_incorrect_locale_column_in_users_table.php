<?php

use Illuminate\Database\Migrations\Migration;

class FixIncorrectLocaleColumnInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update('UPDATE `users` SET `locale` = "en-US" WHERE `locale` = "en"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back
    }
}
