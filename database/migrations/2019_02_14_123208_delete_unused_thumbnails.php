<?php

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
        $thumbnailPath = public_path('images/route_thumbnails/');

        // May not exist
        if (is_dir($thumbnailPath)) {
            // Remove dots
            $thumbnails = array_diff(scandir($thumbnailPath), array('..', '.'));

            foreach ($thumbnails as $image) {
                $publicKey = explode('_', $image);
                $publicKey = $publicKey[0];

                // If it does not exist..
                if (\App\Models\DungeonRoute::where('public_key', $publicKey)->count() === 0) {
                    // Remove it, @ because we don't care for failures
                    @unlink($thumbnailPath . $image);
                }
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
