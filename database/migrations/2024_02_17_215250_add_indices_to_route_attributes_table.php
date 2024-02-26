<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('route_attributes', function (Blueprint $table) {
            $table->index(['category']);
            $table->index(['key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('route_attributes', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['key']);
        });
    }
};
