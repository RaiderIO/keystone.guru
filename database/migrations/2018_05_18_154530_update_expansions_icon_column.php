<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateExpansionsIconColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Just drop it now, nothign has been added anyways
        Schema::dropIfExists('expansions');
        Schema::create('expansions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('icon_file_id');
            $table->text('name');
            $table->text('color');
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
        // No recovering from a re-creation of the table
    }
}
