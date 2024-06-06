<?php

use App\Models\AffixGroup\AffixGroup;
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

        $affixGroupCache = AffixGroup::with(['season'])->get()->keyBy('id');

        DungeonRoute::whereNull('season_id')->chunk(100, function (Collection $collection) use ($seasonService, $affixGroupCache) {
            /** @var Collection<DungeonRoute> $collection */
            foreach ($collection as $dungeonRoute) {
                /** @var AffixGroup|null $affixGroup */
                $affixGroup = $dungeonRoute->affixes->first();

                /** @var AffixGroup|null $cachedAffixGroup */
                $cachedAffixGroup = $affixGroupCache->get($affixGroup?->id);

                $season = $cachedAffixGroup?->season ??
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
