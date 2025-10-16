<?php

use App\Models\DungeonRoute\DungeonRoute as DungeonRoute;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This update is needed to reset the thumbnail status on all dungeon routes.
        // The thumbnails will be more selectively refreshed in the future, only those routes who have some
        // form of popularity or those who have been viewed/accessed recently.
        DungeonRoute::query()
            ->update([
                'thumbnail_refresh_queued_at' => '2000-01-01 00:00:00',
                'thumbnail_updated_at'        => '2000-01-01 00:00:00',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No going back from this
    }
};
