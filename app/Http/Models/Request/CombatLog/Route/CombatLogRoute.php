<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Collection<CombatLogRouteNpc>   $npcs
 * @property Collection<CombatLogRouteSpell> $spells
 */
class CombatLogRoute implements Arrayable
{
    //    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    public function __construct(
        public CombatLogRouteMetadata      $metadata,
        public CombatLogRouteSettings      $settings,
        public CombatLogRouteChallengeMode $challengeMode,
        public Collection                  $npcs,
        public Collection                  $spells
    ) {
    }


    /**
     * @throws DungeonNotSupportedException
     */
    public function createDungeonRoute(
        SeasonServiceInterface                    $seasonService,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        AffixGroupRepositoryInterface             $affixGroupRepository,
        DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
    ): DungeonRoute {
        try {
            $dungeon = Dungeon::where('challenge_mode_id', $this->challengeMode->challengeModeId)->firstOrFail();
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with challengeModeId %d not found', $this->challengeMode->challengeModeId)
            );
        }

        $currentMappingVersion = $dungeon->currentMappingVersion;

        $dungeonRoute = $dungeonRouteRepository->create([
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => Auth::id() ?? -1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $currentMappingVersion->id,
            'season_id'          => $seasonService->getMostRecentSeasonForDungeon($dungeon)?->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
            'title'              => __($dungeon->name),
            'level_min'          => $this->challengeMode->level,
            'level_max'          => $this->challengeMode->level,
            'expires_at'         => $this->settings->temporary ? Carbon::now()->addHours(
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )->toDateTimeString() : null,
        ]);

        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $currentMappingVersion);

        // Find the correct affix groups that match the affix combination the dungeon was started with
        $currentSeasonForDungeon = $dungeon->getActiveSeason($seasonService);
        if ($currentSeasonForDungeon !== null) {
            $affixIds            = collect($this->challengeMode->affixes);
            $eligibleAffixGroups = $affixGroupRepository->getBySeasonId($currentSeasonForDungeon->id);

            foreach ($eligibleAffixGroups as $eligibleAffixGroup) {
                // If the affix group's affixes are all in $affixIds
                if ($affixIds->diff($eligibleAffixGroup->affixes->pluck('affix_id'))->isEmpty()) {
                    // Couple the affix group to the newly created dungeon route
                    $dungeonRouteAffixGroupRepository->create([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'affix_group_id'   => $eligibleAffixGroup->id,
                    ]);
                }
            }
        }

        return $dungeonRoute;
    }

    public function toArray(): array
    {
        return [
            'metadata'      => $this->metadata->toArray(),
            'settings'      => $this->settings->toArray(),
            'challengeMode' => $this->challengeMode->toArray(),
            'npcs'          => $this->npcs->map(fn(CombatLogRouteNpc $npc) => $npc->toArray())->toArray(),
            'spells'        => $this->spells->map(fn(CombatLogRouteSpell $spell) => $spell->toArray())->toArray(),
        ];
    }

    public static function createFromArray(array $body): CombatLogRoute
    {
        $metadata      = CombatLogRouteMetadata::createFromArray($body['metadata'] ?? []);
        $settings      = CombatLogRouteSettings::createFromArray($body['settings'] ?? []);
        $challengeMode = CombatLogRouteChallengeMode::createFromArray($body['challengeMode']);

        $npcs = collect();
        foreach ($body['npcs'] as $npc) {
            $npcs->push(CombatLogRouteNpc::createFromArray($npc));
        }

        $spells = collect();
        foreach ($body['spells'] ?? [] as $spell) {
            $spells->push(CombatLogRouteSpell::createFromArray($spell));
        }

        return new CombatLogRoute(
            $metadata,
            $settings,
            $challengeMode,
            $npcs,
            $spells
        );
    }
}
