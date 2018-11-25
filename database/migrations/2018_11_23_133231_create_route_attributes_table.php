<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category');
            $table->string('name');
            $table->string('description');
        });

        // Fill the table
        Artisan::call('db:seed', array('--class' => 'RouteAttributesSeeder'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_attributes');
    }
}
