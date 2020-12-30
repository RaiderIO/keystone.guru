<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ConvertModelFieldsToLongTextInMappingChangeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapping_change_logs', function (Blueprint $table) {
            $table->longText('before_model')->change();
            $table->longText('after_model')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapping_change_logs', function (Blueprint $table) {
            $table->string('before_model')->change();
            $table->string('after_model')->change();
        });
    }
}
