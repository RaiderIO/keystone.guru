<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteUnusedThumbnails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete thumbnails
        $publicPath = public_path('images/route_thumbnails/');
        // Remove dots
        $thumbnails = array_diff(scandir($publicPath), array('..', '.'));

        foreach ($thumbnails as $image) {
            $publicKey = explode('_', $image);
            $publicKey = $publicKey[0];

            // If it does not exist..
            if( \App\Models\DungeonRoute::where('public_key', $publicKey)->count() === 0 ){
                // Remove it, @ because we don't care for failures
                @unlink($publicPath . $image);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cannot unwind this, srry
    }
}
