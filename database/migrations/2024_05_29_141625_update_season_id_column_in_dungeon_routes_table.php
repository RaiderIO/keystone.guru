<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seasonService = app()->make(SeasonServiceInterface::class);

        DungeonRoute::whereNull('season_id')->chunk(100, function (Collection $collection) use ($seasonService) {
            /** @var Collection<DungeonRoute> $collection */
            foreach ($collection as $dungeonRoute) {
                $season = $dungeonRoute->getSeasonFromAffixes() ??
                    $seasonService->getMostRecentSeasonForDungeon($dungeonRoute->dungeon) ??
                    $seasonService->getSeasonAt($dungeonRoute->created_at);

                if ($season?->hasDungeon($dungeonRoute->dungeon)) {
                    $dungeonRoute->update([
                        'season_id' => $season->id,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @noinspection SqlWithoutWhere */
        DB::update('
            UPDATE `dungeon_routes` SET `season_id` = null
        ');
    }
};
