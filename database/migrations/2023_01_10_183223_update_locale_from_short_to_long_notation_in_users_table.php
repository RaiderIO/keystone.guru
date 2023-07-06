<?php

use Illuminate\Database\Migrations\Migration;

class UpdateLocaleFromShortToLongNotationInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update('UPDATE `users` SET `locale` = "en-US" WHERE `locale` = "en"');
        DB::update('UPDATE `users` SET `locale` = "ru-RU" WHERE `locale` = "ru"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::update('UPDATE `users` SET `locale` = "en" WHERE `locale` = "en-US"');
        DB::update('UPDATE `users` SET `locale` = "ru" WHERE `locale` = "ru-RU"');
    }
}
