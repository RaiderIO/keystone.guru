<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\FloorCoupling;

class AddDirectionToFloorCouplings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('floor_couplings', function (Blueprint $table) {
            $table->enum('direction', ['equal', 'up', 'down'])->after('floor2_id');
        });

        // Set a public key for all current routes
        FloorCoupling::all()->each(function(FloorCoupling $floorCoupling){
            $floorCoupling->direction = 'equal';
            $floorCoupling->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('floor_couplings', function (Blueprint $table) {
            $table->dropColumn('direction');
        });
    }
}
