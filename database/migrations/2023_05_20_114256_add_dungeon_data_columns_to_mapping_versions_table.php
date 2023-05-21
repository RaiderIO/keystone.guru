<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDungeonDataColumnsToMappingVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->integer('enemy_forces_required')->after('version')->default(0);
            $table->integer('enemy_forces_required_teeming')->after('enemy_forces_required')->nullable()->default(null);
            $table->integer('enemy_forces_shrouded')->after('enemy_forces_required_teeming')->nullable()->default(null);
            $table->integer('enemy_forces_shrouded_zul_gamux')->after('enemy_forces_shrouded')->nullable()->default(null);
            $table->integer('timer_max_seconds')->after('enemy_forces_shrouded_zul_gamux')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->dropColumn('enemy_forces_required');
            $table->dropColumn('enemy_forces_required_teeming');
            $table->dropColumn('enemy_forces_shrouded');
            $table->dropColumn('enemy_forces_shrouded_zul_gamux');
            $table->dropColumn('timer_max_seconds');
        });
    }
}
