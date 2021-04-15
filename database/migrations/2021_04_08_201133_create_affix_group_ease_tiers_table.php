<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffixGroupEaseTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('affix_group_ease_tiers', function (Blueprint $table) {
            $table->id();
            $table->integer('affix_group_id');
            $table->integer('dungeon_id');
            $table->string('tier');
            $table->timestamps();

            $table->index('affix_group_id');
            $table->index('dungeon_id');
            $table->index(['affix_group_id', 'dungeon_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affix_group_ease_tiers');
    }
}
