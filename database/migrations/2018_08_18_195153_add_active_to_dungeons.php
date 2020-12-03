<?php

use App\Models\Dungeon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveToDungeons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->boolean('active')->default(1);
        });

        // Set a public key for all current routes
        Dungeon::all()->each(function(Dungeon $dungeon){
            $dungeon->active = 1;
            $dungeon->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}
