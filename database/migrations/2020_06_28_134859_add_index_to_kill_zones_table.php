<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToKillZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kill_zones', function (Blueprint $table)
        {
            $table->integer('index')->after('lng')->default(-1);
        });

        // https://stackoverflow.com/a/47940669/771270
        DB::raw('
            update kill_zones a
            join (
              select a.id, a.dungeon_route_id, count(*) pos
              from kill_zones a
              left join kill_zones b on a.dungeon_route_id = b.dungeon_route_id
                  and a.id >= b.id
              group by a.id, a.dungeon_route_id
            ) b using(id, dungeon_route_id)
            set a.index = b.pos
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kill_zones', function (Blueprint $table)
        {
            $table->dropColumn('index');
        });
    }
}
