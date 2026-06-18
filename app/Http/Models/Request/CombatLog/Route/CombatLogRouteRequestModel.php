<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Override;

/**
 * @OA\Schema(schema="CombatLogRouteRequest")
 * @OA\Property(property="metadata",type="object",ref="#/components/schemas/CombatLogRouteMetadata")
 * @OA\Property(property="settings",type="object",ref="#/components/schemas/CombatLogRouteSettings")
 * @OA\Property(property="challengeMode",type="object",ref="#/components/schemas/CombatLogRouteChallengeMode")
 * @OA\Property(property="npcs",type="array",items={"$ref":"#/components/schemas/CombatLogRouteNpc"})
 * @OA\Property(property="spells",type="array",items={"$ref":"#/components/schemas/CombatLogRouteSpell"}, nullable=true)
 * @OA\Property(property="playerDeaths",type="array",items={"$ref":"#/components/schemas/CombatLogRoutePlayerDeath"}, nullable=true)
 *
 * @property Collection<CombatLogRouteNpcRequestModel>|null         $npcs
 * @property Collection<CombatLogRouteSpellRequestModel>|null       $spells
 * @property Collection<CombatLogRoutePlayerDeathRequestModel>|null $playerDeaths
 */
class CombatLogRouteRequestModel extends RequestModel implements Arrayable
{
    //    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    public function __construct(
        public ?CombatLogRouteMetadataRequestModel      $metadata = null,
        public ?CombatLogRouteSettingsRequestModel      $settings = null,
        public ?CombatLogRouteChallengeModeRequestModel $challengeMode = null,
        public ?CombatLogRouteRosterRequestModel        $roster = null,
        public ?Collection                              $npcs = null,
        public ?Collection                              $spells = null,
        public ?Collection                              $playerDeaths = null,
    ) {
    }

    /**
     * @throws DungeonNotSupportedException
     */
    public function createDungeonRoute(
        SeasonServiceInterface                    $seasonService,
        SeasonAffixGroupServiceInterface          $seasonAffixGroupService,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
        DungeonRepositoryInterface                $dungeonRepository,
        ?int                                      $userId = null,
    ): DungeonRoute {
        try {
            $dungeon = $dungeonRepository->getByMappingVersion($this->challengeMode->challengeModeId, $this->settings->mappingVersion) ??
                $dungeonRepository->getByChallengeModeIdOrFail($this->challengeMode->challengeModeId);
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with challengeModeId %d not found', $this->challengeMode->challengeModeId),
            );
        }

        // In case there was a mapping version override, we need to find the correct mapping version
        if ($this->settings->mappingVersion !== null) {
            $mappingVersion = $dungeonRepository->getMappingVersionByVersion(
                $dungeon,
                $this->settings->mappingVersion,
            );
        }

        // Fallback if not set or not found
        $mappingVersion ??= $dungeon->getCurrentMappingVersion();

        $currentSeasonForDungeon = $dungeon->getActiveSeason($seasonService);

        // Fully get rid of it when regenerating. It won't be available for a sec but that's okay
        $existingDungeonRoute = $dungeonRouteRepository->findCombatLogRouteByPublicKey($this->settings->publicKey);
        if ($existingDungeonRoute !== null) {
            $existingDungeonRoute->delete();
        }

        $dungeonRoute = $dungeonRouteRepository->create([
            'public_key'         => $existingDungeonRoute?->public_key ?? $dungeonRouteRepository->generateRandomPublicKey(), // @phpstan-ignore nullsafe.neverNull
            'author_id'          => $userId,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $currentSeasonForDungeon?->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
            'title'              => __($dungeon->name),
            'level_min'          => $this->challengeMode->level,
            'level_max'          => $this->challengeMode->level,
            'expires_at'         => $this->settings->temporary ? Carbon::now()->addHours(
                config('keystoneguru.sandbox_dungeon_route_expires_hours'),
            )->toDateTimeString() : null,
        ]);

        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);
        // Initially set the relation so we don't go fetching it from the database initially
        $dungeonRoute->setRelation('killZones', collect());

        // Determine the affix group by timestamp — avoids failure when a dungeon only has a partial
        // set of affixes active (e.g. a single Fortified for non-max-level dungeons).
        if ($currentSeasonForDungeon !== null && $this->challengeMode->start !== null) {
            $affixGroup = $seasonAffixGroupService->getAffixGroupAt(
                $currentSeasonForDungeon,
                Carbon::parse($this->challengeMode->start),
                // Region is unknown here; null defaults to US, which is a best-guess approximation.
            );

            if ($affixGroup !== null) {
                $dungeonRouteAffixGroupRepository->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'affix_group_id'   => $affixGroup->id,
                ]);
            }
        }

        return $dungeonRoute;
    }

    #[Override]
    public static function getCollectionItemType(string $key): ?string
    {
        return match ($key) {
            'npcs'         => CombatLogRouteNpcRequestModel::class,
            'spells'       => CombatLogRouteSpellRequestModel::class,
            'playerDeaths' => CombatLogRoutePlayerDeathRequestModel::class,
            default        => null,
        };
    }
}
