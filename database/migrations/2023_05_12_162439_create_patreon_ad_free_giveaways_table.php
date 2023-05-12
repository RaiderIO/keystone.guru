<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatreonAdFreeGiveawaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patreon_ad_free_giveaways', function (Blueprint $table) {
            $table->id();
            $table->integer('giver_user_id');
            $table->integer('receiver_user_id');
            $table->timestamps();

            $table->index(['giver_user_id']);
            $table->index(['receiver_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patreon_ad_free_giveaways');
    }
}
