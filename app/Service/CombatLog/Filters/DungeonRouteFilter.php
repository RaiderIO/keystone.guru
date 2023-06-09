<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Service\CombatLog\Exceptions\AdvancedLogNotEnabledException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
use Exception;

class DungeonRouteFilter implements CombatLogParserInterface
{
    private SeasonServiceInterface $seasonService;
    private ?DungeonRoute          $dungeonRoute = null;
    /**
     * @param SeasonServiceInterface $seasonService
     */
    public function __construct(SeasonServiceInterface $seasonService)
    {
        $this->seasonService = $seasonService;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int       $lineNr
     *
     * @return bool
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        if ($combatLogEvent instanceof CombatLogVersion && !$combatLogEvent->isAdvancedLogEnabled()) {
            throw new AdvancedLogNotEnabledException(
                'Advanced combat logging must be enabled in order to create a dungeon route from a combat log!'
            );
        } elseif ($combatLogEvent instanceof ChallengeModeStart) {
            try {
                $dungeon = Dungeon::where('map_id', $combatLogEvent->getInstanceID())->firstOrFail();
            } catch (Exception $exception) {
                throw new DungeonNotSupportedException(
                    sprintf('Dungeon with instance ID %d not found', $combatLogEvent->getInstanceID())
                );
            }

            $currentMappingVersion = $dungeon->getCurrentMappingVersion();

            $this->dungeonRoute = DungeonRoute::create([
                'public_key'         => DungeonRoute::generateRandomPublicKey(),
                'author_id'          => 1,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $currentMappingVersion->id,
                'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
                'title'              => __($dungeon->name),
                'level_min'          => $combatLogEvent->getKeystoneLevel(),
                'level_max'          => $combatLogEvent->getKeystoneLevel(),
                'expires_at'         => Carbon::now()->addHours(
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                )->toDateTimeString(),
            ]);

            $this->dungeonRoute->dungeon        = $dungeon;
            $this->dungeonRoute->mappingVersion = $currentMappingVersion;

            // Find the correct affix groups that match the affix combination the dungeon was started with
            $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
            if ($currentSeasonForDungeon !== null) {
                $affixIds            = collect($combatLogEvent->getAffixIDs());
                $eligibleAffixGroups = AffixGroup::where('season_id', $currentSeasonForDungeon->id)->get();
                foreach ($eligibleAffixGroups as $eligibleAffixGroup) {
                    // If the affix group's affixes are all in $affixIds
                    if ($affixIds->diff($eligibleAffixGroup->affixes->pluck('affix_id'))->isEmpty()) {
                        // Couple the affix group to the newly created dungeon route
                        DungeonRouteAffixGroup::create([
                            'dungeon_route_id' => $this->dungeonRoute->id,
                            'affix_group_id'   => $eligibleAffixGroup->id,
                        ]);
                    }
                }
            }

            return true;
        } // Otherwise, we skip all events until we are fully initialized

        return false;
    }

    /**
     * @return DungeonRoute|null
     */
    public function getDungeonRoute(): ?DungeonRoute
    {
        return $this->dungeonRoute;
    }
}