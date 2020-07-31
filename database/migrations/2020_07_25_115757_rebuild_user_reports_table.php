<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RebuildUserReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user_reports');
        Schema::create('user_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('model_id');
            $table->string('model_class');
            $table->integer('user_id');
            $table->string('username')->nullable(true);
            $table->string('category');
            $table->string('message');
            $table->boolean('contact_ok')->default(false);
            $table->integer('status')->default(0);
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
        // Don't matter if it's not re-created the old way. It wasn't used yet anyways.
    }
}
