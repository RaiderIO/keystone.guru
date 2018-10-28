<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLegalAgreedColumnToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // I've always wanted to know how fast people agree to the terms. I'd be surprised if it's slower than 10
            // seconds for most users
            $table->integer('legal_agreed_ms')->after('raw_patreon_response_data')->default(0);
            $table->boolean('legal_agreed')->after('raw_patreon_response_data')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('legal_agreed_ms');
            $table->dropColumn('legal_agreed');
        });
    }
}
