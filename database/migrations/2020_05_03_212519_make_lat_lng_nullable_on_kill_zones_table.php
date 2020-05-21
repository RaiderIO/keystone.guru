<?php

use Illuminate\Database\Migrations\Migration;

class MakeLatLngNullableOnKillZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Cannot do normal migration stuffs as https://stackoverflow.com/questions/56864556/migration-cannot-change-double-data-type-value
        DB::statement('alter table kill_zones modify lat double null;');
        DB::statement('alter table kill_zones modify lng double null;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('alter table kill_zones modify lat double not null;');
        DB::statement('alter table kill_zones modify lng double not null;');
    }
}
