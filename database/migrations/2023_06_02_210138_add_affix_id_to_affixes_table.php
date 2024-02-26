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
        Schema::table('affixes', function (Blueprint $table) {
            $table->integer('affix_id')->after('icon_file_id');

            $table->index('affix_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affixes', function (Blueprint $table) {
            $table->dropColumn('affix_id');
        });
    }
};
