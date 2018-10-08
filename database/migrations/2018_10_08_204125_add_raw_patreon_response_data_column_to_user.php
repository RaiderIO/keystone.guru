<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRawPatreonResponseDataColumnToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // I'll remove this soon, but there's no info about the response data. I gotta store it from someone who is a patron.
        // Capture the data, fix the issues, drop the column, everyone happy.
        Schema::table('users', function (Blueprint $table) {
            $table->longText('raw_patreon_response_data')->after('remember_token');
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
            $table->dropColumn('raw_patreon_response_data');
        });
    }
}
