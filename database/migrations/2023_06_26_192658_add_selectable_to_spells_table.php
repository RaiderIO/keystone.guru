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
        Schema::table('spells', function (Blueprint $table) {
            $table->boolean('selectable')->default(false);

            $table->index(['selectable']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn('selectable');
        });
    }
};
