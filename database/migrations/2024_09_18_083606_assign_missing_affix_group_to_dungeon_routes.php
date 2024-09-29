<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
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

        $correctedDungeonCount = 0;
        DungeonRoute::doesntHave('affixGroups')
            ->chunk(100, function (Collection $collection) use ($seasonService, &$correctedDungeonCount) {
                /** @var Collection<DungeonRoute> $collection */
                foreach ($collection as $dungeonRoute) {
                    $season = $dungeonRoute->season ??
                        $seasonService->getMostRecentSeasonForDungeon($dungeonRoute->dungeon) ??
                        $seasonService->getSeasonAt($dungeonRoute->created_at);

                    $currentAffixGroup = $season?->getCurrentAffixGroup();
                    if ($currentAffixGroup !== null) {
                        DungeonRouteAffixGroup::insert([
                            'dungeon_route_id' => $dungeonRoute->id,
                            'affix_group_id'   => $currentAffixGroup->id,
                        ]);

                        $correctedDungeonCount++;
                    }
                }
            });

        info(sprintf('Corrected %s dungeons with no affixes.', $correctedDungeonCount));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot do this
    }
};
