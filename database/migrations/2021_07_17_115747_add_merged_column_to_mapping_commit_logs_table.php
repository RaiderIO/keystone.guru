<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMergedColumnToMappingCommitLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapping_commit_logs', function (Blueprint $table) {
            $table->boolean('merged')->after('id')->default(0);
        });

        DB::update('UPDATE mapping_commit_logs SET merged = 1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapping_commit_logs', function (Blueprint $table) {
            $table->removeColumn('merged');
        });
    }
}
