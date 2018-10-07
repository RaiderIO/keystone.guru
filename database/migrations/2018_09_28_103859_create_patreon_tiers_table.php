<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatreonTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // These are paid tiers that are unlocked through patreon.
        Schema::create('patreon_tiers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('patreon_data_id');
            $table->integer('paid_tier_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patreon_tiers');
    }
}
