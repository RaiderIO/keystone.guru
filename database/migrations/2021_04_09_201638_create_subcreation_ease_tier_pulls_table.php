<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubcreationEaseTierPullsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subcreation_ease_tier_pulls', function (Blueprint $table) {
            $table->id();
            $table->string('source_url');
            $table->string('current_affixes');
            $table->dateTime('last_updated_at');
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
        Schema::dropIfExists('subcreation_ease_tier_pulls');
    }
}
