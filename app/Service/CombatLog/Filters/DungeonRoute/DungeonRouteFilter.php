<?php

namespace App\Service\CombatLog\Filters\DungeonRoute;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Service\CombatLog\Exceptions\AdvancedLogNotEnabledException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use Random\RandomException;

class DungeonRouteFilter implements CombatLogParserInterface
{
    private ?DungeonRoute $dungeonRoute = null;

    public function __construct(private readonly SeasonServiceInterface $seasonService)
    {
    }

    /**
     * @param bool $waitForChallengeModeStart True to wait for a ChallengeModeStart event before parsing, otherwise ZONE_CHANGE will be used
     *
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws RandomException
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr, bool $waitForChallengeModeStart = true): bool
    {
        if ($combatLogEvent instanceof CombatLogVersion && !$combatLogEvent->isAdvancedLogEnabled()) {
            throw new AdvancedLogNotEnabledException(
                'Advanced combat logging must be enabled in order to create a dungeon route from a combat log!',
            );
        } elseif ($combatLogEvent instanceof ChallengeModeStart || $combatLogEvent instanceof ZoneChange) {
            $dungeon       = null;
            $keystoneLevel = null;

            if ($combatLogEvent instanceof ChallengeModeStart) {
                try {
                    $dungeon = Dungeon::where('challenge_mode_id', $combatLogEvent->getChallengeModeID())->firstOrFail();
                } catch (Exception) {
                    throw new DungeonNotSupportedException(
                        sprintf('Dungeon with instance ID %d not found', $combatLogEvent->getInstanceID()),
                    );
                }

                $keystoneLevel = $combatLogEvent->getKeystoneLevel();
            } elseif ($combatLogEvent instanceof ZoneChange) {
                if ($this->dungeonRoute !== null) {
                    // We already have a dungeon route, so we don't need to create a new one
                    return false;
                }

                try {
                    $dungeon = Dungeon::where('map_id', $combatLogEvent->getZoneId())->firstOrFail();
                } catch (Exception) {
                    throw new DungeonNotSupportedException(
                        sprintf('Dungeon with zone ID %d not found', $combatLogEvent->getZoneId()),
                    );
                }
            }

            $currentMappingVersion = $dungeon->getCurrentMappingVersion();
            $mostRecentSeason      = $this->seasonService->getMostRecentSeasonForDungeon($dungeon);

            $this->dungeonRoute = DungeonRoute::create([
                'public_key'         => DungeonRoute::generateRandomPublicKey(),
                'author_id'          => 1,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $currentMappingVersion->id,
                'season_id'          => $mostRecentSeason?->id,
                'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
                'title'              => __($dungeon->name, [], 'en_US'),
                'level_min'          => $keystoneLevel ?? $mostRecentSeason?->key_level_min ?? 2,
                'level_max'          => $keystoneLevel ?? $mostRecentSeason?->key_level_max ?? 20,
                'expires_at'         => Carbon::now()->addHours(
                    config('keystoneguru.sandbox_dungeon_route_expires_hours'),
                )->toDateTimeString(),
            ]);

            $this->dungeonRoute->setRelation('dungeon', $dungeon);
            $this->dungeonRoute->setRelation('mappingVersion', $currentMappingVersion);

            // Find the correct affix groups that match the affix combination the dungeon was started with
            $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
            if ($currentSeasonForDungeon !== null && $combatLogEvent instanceof ChallengeModeStart) {
                $affixGroups = AffixGroup::findMatchingAffixGroupsForAffixIds(
                    $currentSeasonForDungeon,
                    collect($combatLogEvent->getAffixIDs()),
                );

                foreach ($affixGroups as $affixGroup) {
                    DungeonRouteAffixGroup::create([
                        'dungeon_route_id' => $this->dungeonRoute->id,
                        'affix_group_id'   => $affixGroup->id,
                    ]);
                }
            }

            return true;
        } // Otherwise, we skip all events until we are fully initialized

        return false;
    }

    public function getDungeonRoute(): ?DungeonRoute
    {
        return $this->dungeonRoute;
    }
}
