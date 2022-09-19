<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidTierKeyToPaidTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paid_tiers', function (Blueprint $table) {
            $table->string('key')->after('id');

            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paid_tiers', function (Blueprint $table) {
            $table->dropIndex('key');
            $table->dropColumn('key');
        });
    }
}
