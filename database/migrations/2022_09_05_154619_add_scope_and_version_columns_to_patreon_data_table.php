<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScopeAndVersionColumnsToPatreonDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patreon_data', function (Blueprint $table) {
            $table->string('scope')->after('email');
            $table->string('version')->after('refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patreon_data', function (Blueprint $table) {
            $table->dropColumn('scope');
            $table->dropColumn('version');
        });
    }
}
