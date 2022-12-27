<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPatreonIdColumnToPatreonUserLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patreon_user_links', function (Blueprint $table) {
            $table->string('patreon_id')->nullable()->after('user_id');

            $table->index(['patreon_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patreon_user_links', function (Blueprint $table) {
            $table->dropColumn('patreon_id');
        });
    }
}
