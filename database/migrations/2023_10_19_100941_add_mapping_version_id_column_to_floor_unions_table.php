<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMappingVersionIdColumnToFloorUnionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('floor_unions', function (Blueprint $table) {
            $table->integer('mapping_version_id')->after('id');

            $table->index(['mapping_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('floor_unions', function (Blueprint $table) {
            $table->dropColumn('mapping_version_id');
        });
    }
}