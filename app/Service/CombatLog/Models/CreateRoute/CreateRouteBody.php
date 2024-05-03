<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Repositories\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class CreateRouteBody
{
    //    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    public function __construct(
        public CreateRouteMetadata      $metadata,
        public CreateRouteSettings      $settings,
        public CreateRouteChallengeMode $challengeMode,
        /** @var Collection<CreateRouteNpc> */
        public Collection               $npcs,
        /** @var Collection<CreateRouteSpell> */
        public Collection               $spells
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
            if ($this->challengeMode->challengeModeId !== null) {
                $dungeon = Dungeon::where('challenge_mode_id', $this->challengeMode->challengeModeId)->firstOrFail();
            } else {
                $dungeon = Dungeon::where('map_id', $this->challengeMode->mapId)->firstOrFail();
            }
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with instance ID %d not found', $this->challengeMode->mapId)
            );
        }

        $currentMappingVersion = $dungeon->currentMappingVersion;

        $dungeonRoute = $dungeonRouteRepository->create([
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => Auth::id() ?? -1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $currentMappingVersion->id,
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

    public static function createFromArray(array $body): CreateRouteBody
    {
        $metadata      = CreateRouteMetadata::createFromArray($body['metadata'] ?? []);
        $settings      = CreateRouteSettings::createFromArray($body['settings'] ?? []);
        $challengeMode = CreateRouteChallengeMode::createFromArray($body['challengeMode']);

        $npcs = collect();
        foreach ($body['npcs'] as $npc) {
            $npcs->push(CreateRouteNpc::createFromArray($npc));
        }

        $spells = collect();
        foreach ($body['spells'] ?? [] as $spell) {
            $spells->push(CreateRouteSpell::createFromArray($spell));
        }

        return new CreateRouteBody(
            $metadata,
            $settings,
            $challengeMode,
            $npcs,
            $spells
        );
    }
}
