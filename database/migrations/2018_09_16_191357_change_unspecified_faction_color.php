<?php

use App\Models\Faction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeUnspecifiedFactionColor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('factions')->where('key', Faction::FACTION_UNSPECIFIED)->update(['color' => 'inherit']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //  Too bad
    }
}
