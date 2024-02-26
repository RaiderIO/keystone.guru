<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropIndex(['floor_id', 'mapping_version_id']);

            $table->index(['floor_id']);
            $table->index(['mapping_version_id']);
            $table->index(['mapping_version_id', 'floor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->index(['floor_id', 'mapping_version_id']);

            $table->dropIndex(['floor_id']);
            $table->dropIndex(['mapping_version_id']);
            $table->dropIndex(['mapping_version_id', 'floor_id']);
        });
    }
};
