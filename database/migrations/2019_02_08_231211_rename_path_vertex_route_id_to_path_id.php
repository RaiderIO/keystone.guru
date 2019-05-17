<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamePathVertexRouteIdToPathId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('path_vertices', function (Blueprint $table) {
            $table->renameColumn('route_id', 'path_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('path_vertices', function (Blueprint $table) {
            $table->renameColumn('path_id', 'route_id');
        });
    }
}
