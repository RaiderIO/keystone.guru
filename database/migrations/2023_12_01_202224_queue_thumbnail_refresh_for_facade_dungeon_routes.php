<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class extends Migration
{
    private $thumbnailService;

    public function __construct()
    {
        $this->thumbnailService = app(ThumbnailServiceInterface::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DungeonRoute::select('dungeon_routes.*')
            ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
            ->where('dungeons.facade_enabled', true)
            ->chunk(100, function (Collection $dungeonRoutes) {
                foreach ($dungeonRoutes as $dungeonRoute) {
                    $this->thumbnailService->queueThumbnailRefresh($dungeonRoute);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back
    }
};
