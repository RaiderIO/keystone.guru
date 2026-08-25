<?php

namespace App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteChallengeModeRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteCoordRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteCorrectionRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteMetadataRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRoutePlayerDeathRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRosterRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteSettingsRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteSpellRequestDto;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndSpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartSpecialEvent;
use App\Logic\Structs\IngameXY;
use App\Models\Brushline;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Polyline;
use App\Models\Season;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
// The Stub\* repositories below are imported concretely on purpose and must NOT be replaced by their interfaces: the
// container binds those interfaces to the persisting Database\* implementations. See the docblocks on
// convertCombatLogRouteToCombatLogEvents() and correctCombatLogRoute() for why those two flows must not persist.
use App\Repositories\Stub\DungeonRoute\DungeonRouteAffixGroupRepository as DungeonRouteAffixGroupRepositoryStub;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository as DungeonRouteRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository as KillZoneEnemyRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneRepository as KillZoneRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository as KillZoneSpellRepositoryStub;
use App\Repositories\Swoole\Interfaces\DungeonRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\EnemyRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\FloorRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\NpcRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use App\Service\CombatLog\Builders\CombatLogRouteCombatLogEventsBuilder;
use App\Service\CombatLog\Builders\CombatLogRouteCorrectionBuilder;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\CombatLog\Exceptions\CombatLogRouteRegeneratedConcurrentlyException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Logging\CombatLogRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\ResultEvents\CombatantInfo as CombatantInfoResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyKilled as EnemyKilledResultEvent;
use App\Service\CombatLog\ResultEvents\PlayerDied as PlayerDiedResultEvent;
use App\Service\CombatLog\ResultEvents\SpellCast as SpellCastResultEvent;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\DungeonRoute\DungeonRouteUpgradeDraftServiceInterface;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftGoneException;
use App\Service\DungeonRoute\MapDrawingServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceStub;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Season\SeasonServiceStub;
use Auth;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Orchestrates combat log parsing: runs filters to produce result events, then builds a DungeonRoute with kill zones
 * via CombatLogRouteDungeonRouteBuilder.
 */
class CombatLogRouteDungeonRouteService implements CombatLogRouteDungeonRouteServiceInterface
{
    /** @var int A combat log carries no Raider.IO keystone run id - locally parsed routes all share this one. */
    private const METADATA_PLACEHOLDER_KEYSTONE_RUN_ID = 98765;

    /** @var int As above, for the logged run id. */
    private const METADATA_PLACEHOLDER_LOGGED_RUN_ID = 87654;

    /** @var string A combat log does not name the region it was recorded on. */
    private const METADATA_PLACEHOLDER_REGION = GameServerRegion::EUROPE;

    /** @var string A combat log does not name the realm type it was recorded on. */
    private const METADATA_PLACEHOLDER_REALM_TYPE = 'live';

    public function __construct(
        protected readonly CombatLogService                                  $combatLogService,
        protected readonly SeasonServiceInterface                            $seasonService,
        protected readonly CoordinatesServiceInterface                       $coordinatesService,
        protected readonly MapDrawingServiceInterface                        $mapDrawingService,
        protected readonly DungeonRouteRepositoryInterface                   $dungeonRouteRepository,
        protected readonly DungeonRouteAffixGroupRepositoryInterface         $dungeonRouteAffixGroupRepository,
        protected readonly SeasonAffixGroupServiceInterface                  $seasonAffixGroupService,
        protected readonly KillZoneRepositoryInterface                       $killZoneRepository,
        protected readonly KillZoneEnemyRepositoryInterface                  $killZoneEnemyRepository,
        protected readonly KillZoneSpellRepositoryInterface                  $killZoneSpellRepository,
        protected readonly EnemyRepositoryInterface                          $enemyRepository,
        protected readonly NpcRepositoryInterface                            $npcRepository,
        protected readonly SpellRepositoryInterface                          $spellRepository,
        protected readonly FloorRepositoryInterface                          $floorRepository,
        protected readonly DungeonRepositoryInterface                        $dungeonRepository,
        // Swoole
        protected readonly EnemyRepositorySwooleInterface                    $enemyRepositorySwoole,
        protected readonly NpcRepositorySwooleInterface                      $npcRepositorySwoole,
        protected readonly SpellRepositorySwooleInterface                    $spellRepositorySwoole,
        protected readonly FloorRepositorySwooleInterface                    $floorRepositorySwoole,
        protected readonly DungeonRepositorySwooleInterface                  $dungeonRepositorySwoole,
        protected readonly DungeonRouteUpgradeDraftServiceInterface          $dungeonRouteUpgradeDraftService,
        protected readonly CombatLogRouteDungeonRouteServiceLoggingInterface $log,
    ) {
    }

    /**
     * @throws DungeonNotSupportedException
     * @throws CombatLogRouteRegeneratedConcurrentlyException
     * @throws Exception
     */
    public function convertCombatLogRouteToDungeonRoute(CombatLogRouteRequestDto $combatLogRoute): DungeonRoute
    {
        // Regeneration (settings->publicKey set) replaces the contents of an existing combat log route. That route -
        // and the ChallengeModeRun that makes it one - stays untouched until the replacement is completely built, so a
        // failure anywhere below leaves it exactly as it was and the regeneration can simply be retried (#4194).
        // findCombatLogRouteByPublicKey() only matches routes that have a run, which is the guard that stops the
        // API from overwriting arbitrary routes by public key.
        $existingDungeonRoute = $this->dungeonRouteRepository->findCombatLogRouteByPublicKey($combatLogRoute->settings->publicKey);

        if ($existingDungeonRoute !== null) {
            // Only one draft may exist per route (dungeon_routes_upgrade_of_unique), and a regeneration takes over
            // whatever it finds: a draft here is all but certainly the wreckage of an earlier ARC run that died
            // between creating it and applying it, and blocking every future regeneration on it would be worse than
            // discarding it (#4297).
            $abandonedDraft = $existingDungeonRoute->upgradeDraft()->first();
            if ($abandonedDraft !== null) {
                $this->log->convertCombatLogRouteToDungeonRouteDiscardingAbandonedDraft($existingDungeonRoute->id, $abandonedDraft->id);
                $abandonedDraft->delete();
            }
        }

        $builder = new CombatLogRouteDungeonRouteBuilder(
            $this->seasonService,
            $this->seasonAffixGroupService,
            $this->coordinatesService,
            $this->dungeonRouteRepository,
            $this->dungeonRouteAffixGroupRepository,
            $this->killZoneRepository,
            $this->killZoneEnemyRepository,
            $this->killZoneSpellRepository,
            $this->enemyRepository,
            $this->npcRepository,
            $this->spellRepository,
            $this->floorRepository,
            $this->dungeonRepository,
            $combatLogRoute,
            Auth::id() ?? -1,
        );

        try {
            if ($existingDungeonRoute !== null) {
                // The route the builder just created IS the draft - it holds exactly the content the fresh combat log
                // produced, which is what apply() then writes onto the original. The DungeonRoute::saving hook flips
                // it to UNPUBLISHED on this write, which is what a draft should be: it must never be reachable in its
                // own right, the original keeps serving its public key throughout.
                $this->markAsUpgradeDraft($builder->getDungeonRoute(), $existingDungeonRoute);
            }

            $dungeonRoute = $builder->build();

            $this->saveCombatLogRouteEnemyFailures($dungeonRoute->mappingVersion, $combatLogRoute, $dungeonRoute);

            if ($combatLogRoute->settings->debugIcons) {
                $this->generateMapIcons(
                    $dungeonRoute->mappingVersion,
                    $combatLogRoute,
                    $dungeonRoute,
                );
            }
        } catch (Throwable $throwable) {
            // The builder's constructor already created the new route - don't leave a half-built one behind
            $newDungeonRoute = $builder->getDungeonRoute();
            $this->log->convertCombatLogRouteToDungeonRouteBuildFailedDeletingNewRoute($newDungeonRoute->id, $throwable->getMessage());
            $newDungeonRoute->delete();

            throw $throwable;
        }

        // Only now that the draft is complete does its content replace the original's
        if ($existingDungeonRoute === null) {
            $this->saveChallengeModeRun($combatLogRoute, $dungeonRoute);

            return $dungeonRoute;
        }

        return $this->applyRegeneratedDungeonRoute($existingDungeonRoute, $dungeonRoute);
    }

    /**
     * Marks the freshly created, still empty route as the upgrade draft of the route being regenerated, so that the
     * combat log's output lands in a draft rather than in a second standalone route. Apply then writes that content
     * onto the original, which keeps its id - and with it every inbound reference and every relation the old
     * "build a new row and hand it the public key" replacement used to throw away (#4297).
     *
     * dungeon_routes_upgrade_of_unique is what makes this the arbiter between two regenerations of the same route
     * that overlap: the second one to get here finds the index taken and loses, rather than both of them building
     * into the same draft. The caller's catch removes the half-built route this one had already created.
     *
     * A regeneration that starts *later* still takes this draft over mid-build (see the discard in
     * convertCombatLogRouteToDungeonRoute()). This one then fails somewhere in the builder - most likely on
     * DungeonRouteBuilder::buildFinished()'s find()->update() against the deleted row - rather than with a tidy
     * exception, but the outcome is the same either way: the caller's catch cleans up after it and rethrows, and
     * the original is never touched by the loser.
     *
     * @throws CombatLogRouteRegeneratedConcurrentlyException
     */
    private function markAsUpgradeDraft(DungeonRoute $draft, DungeonRoute $existingDungeonRoute): void
    {
        try {
            $draft->update(['upgrade_of_dungeon_route_id' => $existingDungeonRoute->id]);
        } catch (UniqueConstraintViolationException) {
            throw new CombatLogRouteRegeneratedConcurrentlyException(
                sprintf(
                    'Route %s is already being regenerated concurrently; discarded this regeneration',
                    $existingDungeonRoute->public_key,
                ),
            );
        }
    }

    /**
     * Writes the completed draft's content onto the route being regenerated and deletes the draft, via the same
     * draft-and-apply path a manual mapping version upgrade uses.
     *
     * The publish invariant is deliberately not enforced here: an ARC route is published by construction, and a
     * combat log that misses a required enemy is a routine outcome rather than a reason to fail the whole
     * regeneration - the pre-#4297 replacement had no such check either.
     *
     * @throws CombatLogRouteRegeneratedConcurrentlyException
     * @throws Throwable
     */
    private function applyRegeneratedDungeonRoute(DungeonRoute $existingDungeonRoute, DungeonRoute $draft): DungeonRoute
    {
        $draftId = $draft->id;

        try {
            $dungeonRoute = $this->dungeonRouteUpgradeDraftService->apply($draft, enforcePublishInvariant: false);
        } catch (UpgradeDraftGoneException) {
            // Another regeneration of the same route took this draft over while this one was still building. Its
            // row is already gone, but delete() still fires DungeonRoute::deleting, which cleans up the kill zones
            // and map icons this build wrote against that id.
            $this->log->applyRegeneratedDungeonRouteDraftTakenOver($existingDungeonRoute->public_key, $existingDungeonRoute->id, $draftId);
            $draft->delete();

            throw new CombatLogRouteRegeneratedConcurrentlyException(
                sprintf('Route %s was regenerated concurrently; discarded this regeneration', $existingDungeonRoute->public_key),
            );
        }

        // Enemy failures live on the combatlog connection and are not part of the content apply() moves, so they are
        // re-pointed by hand. Without this they would stay on the deleted draft's id and the original would keep
        // accumulating the failures of every previous generation - which the old path got away with only because it
        // deleted the whole route each time.
        CombatLogRouteEnemyFailure::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();
        CombatLogRouteEnemyFailure::query()->where('dungeon_route_id', $draftId)->update(['dungeon_route_id' => $dungeonRoute->id]);

        // settings->temporary applies to the route that comes out of this regeneration, exactly as it did when that
        // route was a brand new row. apply() preserves the original's own expiry, so it is assigned here instead.
        DungeonRoute::query()->whereKey($dungeonRoute->id)->update(['expires_at' => $draft->expires_at]);
        $dungeonRoute->expires_at = $draft->expires_at;

        $this->log->applyRegeneratedDungeonRouteApplied($dungeonRoute->public_key, $dungeonRoute->id, $draftId);

        return $dungeonRoute;
    }

    /**
     * Converts a combat log route into CombatLogEvents WITHOUT persisting anything: the DungeonRoute and its kill zones
     * are only scaffolding needed to place the events on the map, and the caller discards them.
     *
     * The builder writes through repositories, so the ones that would otherwise create rows are passed as explicit
     * Stub\* instances rather than resolved from the container - the container binds those interfaces to the persisting
     * Database\* implementations. The read-only repositories ($enemyRepository and friends) are the injected ones.
     *
     * @throws DungeonNotSupportedException
     * @throws Exception
     */
    public function convertCombatLogRouteToCombatLogEvents(CombatLogRouteRequestDto $combatLogRoute): Collection
    {
        $builder = new CombatLogRouteCombatLogEventsBuilder(
            $this->seasonService,
            new SeasonAffixGroupServiceStub(),
            $this->coordinatesService,
            new DungeonRouteRepositoryStub(),
            new DungeonRouteAffixGroupRepositoryStub(),
            new KillZoneRepositoryStub(),
            new KillZoneEnemyRepositoryStub(),
            new KillZoneSpellRepositoryStub(),
            $this->enemyRepository,
            $this->npcRepository,
            $this->spellRepository,
            $this->floorRepository,
            $this->dungeonRepository,
            $combatLogRoute,
        );

        $builder->build();

        return $builder->getCombatLogEvents();
    }

    /**
     * Corrects a combat log route in-place and hands the corrected route back to the caller. Nothing is persisted - the
     * DungeonRoute built along the way exists only so the builder can resolve enemies/floors, and is thrown away.
     *
     * Same reasoning as convertCombatLogRouteToCombatLogEvents(): the writing repositories are explicit Stub\* instances
     * because the container binds their interfaces to the persisting Database\* implementations. The read-only ones pick
     * the Swoole variant when running on Octane, which caches its data across requests instead of hitting the DB.
     *
     * @throws DungeonNotSupportedException
     * @throws Exception
     */
    public function correctCombatLogRoute(
        CombatLogRouteRequestDto $combatLogRoute,
    ): CombatLogRouteCorrectionRequestDto {
        $isSwoole = onSwooleServer();

        $builder = new CombatLogRouteCorrectionBuilder(
            new SeasonServiceStub(),
            new SeasonAffixGroupServiceStub(),
            $this->coordinatesService,
            new DungeonRouteRepositoryStub(),
            new DungeonRouteAffixGroupRepositoryStub(),
            new KillZoneRepositoryStub(),
            new KillZoneEnemyRepositoryStub(),
            new KillZoneSpellRepositoryStub(),
            $isSwoole ? $this->enemyRepositorySwoole : $this->enemyRepository,
            $isSwoole ? $this->npcRepositorySwoole : $this->npcRepository,
            $isSwoole ? $this->spellRepositorySwoole : $this->spellRepository,
            $isSwoole ? $this->floorRepositorySwoole : $this->floorRepository,
            $isSwoole ? $this->dungeonRepositorySwoole : $this->dungeonRepository,
            $combatLogRoute,
        );

        $builder->build();

        return $builder->getCombatLogRoute();
    }

    /**
     * @throws Exception
     */
    public function getCombatLogRoute(
        string $combatLogFilePath,
        bool   $dungeonOrRaid = false,
        bool   $debugIcons = false,
    ): ?CombatLogRouteRequestDto {
        ini_set('max_execution_time', 1800);

        try {
            $this->log->getCombatLogRouteStart($combatLogFilePath);
            $dungeonRoute = null;

            if ($dungeonOrRaid) {
                $resultEvents = $this->combatLogService->getResultEventsForDungeonOrRaid($combatLogFilePath, $dungeonRoute);
                if (!($dungeonRoute instanceof DungeonRoute)) {
                    $this->log->getCombatLogRouteUnableToGenerateDungeonRouteFromDungeonOrRaid();

                    return null;
                }

                $seconds       = random_int(1200, 2400);
                $milliseconds  = $seconds * 1000;
                $runStart      = Carbon::now()->subSeconds($seconds);
                $wowInstanceId = null;

                $challengeMode = new CombatLogRouteChallengeModeRequestDto(
                    $runStart->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                    Carbon::now()->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                    true,
                    $milliseconds,
                    $milliseconds,
                    $dungeonRoute->mappingVersion->timer_max_seconds === 0 ?
                        1 : $milliseconds / ($dungeonRoute->mappingVersion->timer_max_seconds * 1000),
                    $dungeonRoute->dungeon->challenge_mode_id,
                    random_int(2, 20),
                    0,
                    null,
                );
            } else {
                $resultEvents = $this->combatLogService->getResultEventsForChallengeMode($combatLogFilePath, $dungeonRoute);

                if (!($dungeonRoute instanceof DungeonRoute)) {
                    $this->log->getCombatLogRouteUnableToGenerateDungeonRouteFromChallengeMode();

                    return null;
                }
                /** @var ChallengeModeStartSpecialEvent $challengeModeStartEvent */
                $challengeModeStartEvent = $resultEvents->filter(static fn(
                    BaseResultEvent $resultEvent,
                ) => $resultEvent instanceof ChallengeModeStartResultEvent)->first()->getChallengeModeStartEvent();

                /** @var ChallengeModeEndSpecialEvent $challengeModeEndEvent */
                $challengeModeEndEvent = $resultEvents->filter(static fn(
                    BaseResultEvent $resultEvent,
                ) => $resultEvent instanceof ChallengeModeEndResultEvent)->first()->getChallengeModeEndEvent();

                $playerDeathEvents = $resultEvents->filter(static fn(
                    BaseResultEvent $resultEvent,
                ) => $resultEvent instanceof PlayerDiedResultEvent);

                $runStart      = $challengeModeStartEvent->getTimestamp();
                $wowInstanceId = $challengeModeStartEvent->getInstanceID();

                // A combat log does not carry the dungeon's par time, but the mapping does - and without it parTimeMs
                // would claim every run finished exactly on the timer.
                $parTimeMs = $dungeonRoute->mappingVersion->timer_max_seconds === 0 ?
                    $challengeModeEndEvent->getTotalTimeMS() : $dungeonRoute->mappingVersion->timer_max_seconds * 1000;

                $challengeMode = new CombatLogRouteChallengeModeRequestDto(
                    $runStart->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                    $challengeModeEndEvent->getTimestamp()->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                    (bool)$challengeModeEndEvent->getSuccess(),
                    $challengeModeEndEvent->getTotalTimeMS(),
                    $parTimeMs,
                    $dungeonRoute->mappingVersion->timer_max_seconds === 0 ?
                        1 : $challengeModeEndEvent->getTotalTimeMS() / ($dungeonRoute->mappingVersion->timer_max_seconds * 1000),
                    $challengeModeStartEvent->getChallengeModeID(),
                    $challengeModeStartEvent->getKeystoneLevel(),
                    $playerDeathEvents->count(),
                    $challengeModeStartEvent->getAffixIDs(),
                );
            }

            // #1818 Filter out any NPC ids that are invalid
            $validNpcIds = $this->npcRepository->getInUseNpcIds($dungeonRoute->mappingVersion);

//            dd($validNpcIds->pluck('id')->toArray());

            $npcs             = collect();
            $npcEngagedEvents = collect();
            $spells           = collect();
            $playerDeaths     = collect();
            /** @var Collection<string, CombatantInfoResultEvent> $mostRecentCombatantInfo */
            $mostRecentCombatantInfo        = collect();
            $mostRecentCombatantInfoIndexFn = static function (string $guid) use ($mostRecentCombatantInfo) {
                $index = 0;
                foreach ($mostRecentCombatantInfo as $combatantInfo) {
                    /** @var CombatantInfoResultEvent $combatantInfo */
                    if ($combatantInfo->getGuid()->getGuid() === $guid) {
                        break;
                    }
                    $index++;
                }

                return $index;
            };

            foreach ($resultEvents as $resultEvent) {
                if ($resultEvent instanceof CombatantInfoResultEvent) {
                    $mostRecentCombatantInfo->put($resultEvent->getGuid()->getGuid(), $resultEvent);
                } elseif ($resultEvent instanceof EnemyEngagedResultEvent) {
                    $guid = $resultEvent->getGuid();
                    if ($validNpcIds->search($guid->getId()) === false) {
                        $this->log->getCombatLogRouteEnemyEngagedInvalidNpcId($guid->getId());

                        continue;
                    }

                    $npcEngagedEvents->put($guid->getGuid(), $resultEvent);
                } elseif ($resultEvent instanceof EnemyKilledResultEvent) {
                    $guid = $resultEvent->getGuid();
                    if ($validNpcIds->search($guid->getId()) === false) {
                        $this->log->getCombatLogRouteEnemyKilledInvalidNpcId($guid->getId());

                        continue;
                    }

                    /** @var EnemyEngagedResultEvent $npcEngagedEvent */
                    $npcEngagedEvent = $npcEngagedEvents->get($guid->getGuid());

                    $npcEngagedEvents->forget($guid->getGuid());

                    $npcs->push(
                        new CombatLogRouteNpcRequestDto(
                            $guid->getId(),
                            $guid->getSpawnUID(),
                            $npcEngagedEvent->getEngagedEvent()->getTimestamp()->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                            new CombatLogRouteCoordRequestDto(
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getPositionX(),
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getPositionY(),
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getUiMapId(),
                            ),
                        ),
                    );
                } elseif ($resultEvent instanceof SpellCastResultEvent) {
                    $advancedData = $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData();

                    $spells->push(
                        new CombatLogRouteSpellRequestDto(
                            $resultEvent->getSpellId(),
                            // We use the owner guid if available (in case a pet cast this), otherwise we use the info guid (which is the owner/caster)
                            $advancedData->getOwnerGuid()?->getGuid() ?? $advancedData->getInfoGuid()->getGuid(),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                            new CombatLogRouteCoordRequestDto(
                                $advancedData->getPositionX(),
                                $advancedData->getPositionY(),
                                $advancedData->getUiMapId(),
                            ),
                        ),
                    );
                } elseif ($resultEvent instanceof PlayerDiedResultEvent) {
                    /** @var CombatantInfoResultEvent|null $combatantInfo */
                    $combatantInfo = $mostRecentCombatantInfo->get($resultEvent->getGuid()->getGuid());
                    if ($combatantInfo === null) {
                        $this->log->getCombatLogRoutePlayerDiedUnableToFindCombatantInfo($resultEvent->getGuid()->getGuid());

                        continue;
                    }

                    $playerDeaths->push(
                        new CombatLogRoutePlayerDeathRequestDto(
                            // Extract the index of the combatant consistently
                            $mostRecentCombatantInfo->mapWithKeys(
                                static fn(CombatantInfoResultEvent $combatantInfo, string $guidKey) => [
                                    $mostRecentCombatantInfoIndexFn($guidKey) => $combatantInfo,
                                ],
                            )->search($combatantInfo),
                            $combatantInfo->getClass()->class_id,
                            $combatantInfo->getSpecialization()->specialization_id,
                            $combatantInfo->getCombatantInfoEvent()->getAverageItemLevel(),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                            new CombatLogRouteCoordRequestDto(
                                $resultEvent->getLastKnownEvent()?->getAdvancedData()->getPositionX(),
                                $resultEvent->getLastKnownEvent()?->getAdvancedData()->getPositionY(),
                                $resultEvent->getLastKnownEvent()?->getAdvancedData()->getUiMapId(),
                            ),
                        ),
                    );
                }
            }

            if ($npcEngagedEvents->isNotEmpty()) {
                throw new Exception("Found enemies that weren't killed!");
            }

            return new CombatLogRouteRequestDto(
                $this->getCombatLogRouteMetadata($dungeonRoute, $runStart, $wowInstanceId),
                new CombatLogRouteSettingsRequestDto(true, $debugIcons, $dungeonRoute->mappingVersion->version),
                $challengeMode,
                new CombatLogRouteRosterRequestDto(
                    $mostRecentCombatantInfo->count(),
                    $mostRecentCombatantInfo->map(
                        static fn(
                            CombatantInfoResultEvent $combatantInfo,
                        ) => $combatantInfo->getCombatantInfoEvent()->getAverageItemLevel(),
                    )->average(),
                    // I don't know the Raider.IO character IDs - so just make something up
                    $mostRecentCombatantInfo->map(
                        static fn(
                            CombatantInfoResultEvent $combatantInfo,
                            string                   $guidKey,
                        ) => $mostRecentCombatantInfoIndexFn($guidKey),
                    )->values()->toArray(),
                    $mostRecentCombatantInfo->map(
                        static fn(
                            CombatantInfoResultEvent $combatantInfo,
                        ) => $combatantInfo->getSpecialization()->specialization_id,
                    )->values()->toArray(),
                    $mostRecentCombatantInfo->map(
                        static fn(CombatantInfoResultEvent $combatantInfo) => $combatantInfo->getClass()->class_id,
                    )->values()->toArray(),
                ),
                $npcs,
                $spells,
                $playerDeaths,
            );
        } finally {
            $this->log->getCombatLogRouteEnd();
        }
    }

    /**
     * The metadata a locally parsed combat log can carry. Raider.IO owns most of this - a combat log file has no
     * notion of a keystone/logged run id, nor of the region or realm type it was recorded on - so those stay
     * placeholders. What the log *does* determine is the season the run belongs to, the week within it, and the
     * WoW instance id; those are resolved here rather than hardcoded, because `metadata->season` and
     * `metadata->period` are stored on every combat_log_event the route produces and are what the heatmap filters
     * on. A run recorded under the wrong season string is invisible to those filters.
     */
    private function getCombatLogRouteMetadata(
        DungeonRoute $dungeonRoute,
        Carbon       $runStart,
        ?int         $wowInstanceId,
    ): CombatLogRouteMetadataRequestDto {
        // Deliberately not getSeasonAt(), which scopes to a single expansion: a season routinely contains dungeons
        // from older ones (Midnight season 2 runs King's Rest, a BfA dungeon), so the dungeon's own expansion is
        // the wrong lens. Seasons run back to back across expansions, so the last one to have started is the one
        // the run belongs to.
        /** @var Season|null $season */
        $season = $this->seasonService->getAllSeasons()
            ->filter(static fn(Season $season): bool => $season->start->lessThanOrEqualTo($runStart))
            ->sortByDesc('start')
            ->first() ?? $this->seasonService->getMostRecentSeasonForDungeon($dungeonRoute->dungeon);

        /** @var GameServerRegion $region */
        $region = GameServerRegion::where('short', self::METADATA_PLACEHOLDER_REGION)->firstOrFail();

        return new CombatLogRouteMetadataRequestDto(
            Uuid::uuid4()->toString(),
            self::METADATA_PLACEHOLDER_KEYSTONE_RUN_ID,
            self::METADATA_PLACEHOLDER_LOGGED_RUN_ID,
            // The period is the week of the run in Blizzard's global numbering, which is a function of the date and
            // the region's weekly reset - not of the season - so it is taken from the same region metadata->regionId
            // claims, rather than counted forward from the season's start.
            $region->getKeystoneLeaderboardPeriod($runStart),
            $season === null ? null : sprintf('season-%s-%d', $season->expansion->shortname, $season->index),
            $region->id,
            self::METADATA_PLACEHOLDER_REALM_TYPE,
            $wowInstanceId,
        );
    }

    /**
     * Inserts a fresh run for a route that does not replace an existing one. A regeneration needs nothing here at
     * all: it preserves the original's id, so the original's run still points at it. The run is deliberately no
     * longer looked up by metadata->runId either, which is a client-supplied string shared by hundreds of routes
     * and re-pointed some *other* route's run (#4194).
     */
    private function saveChallengeModeRun(CombatLogRouteRequestDto $combatLogRoute, DungeonRoute $dungeonRoute): void
    {
        $now = Carbon::now();

        /** @var ChallengeModeRun $challengeModeRun */
        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $dungeonRoute->dungeon_id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => $combatLogRoute->challengeMode->level,
            'success'          => $combatLogRoute->challengeMode->success ?? false,
            'total_time_ms'    => $combatLogRoute->challengeMode->durationMs,
            'created_at'       => $now,
        ]);

        ChallengeModeRunData::create([
            'challenge_mode_run_id' => $challengeModeRun->id,
            'run_id'                => $combatLogRoute->metadata->runId,
            'correlation_id'        => correlationId(),
            'post_body'             => json_encode($combatLogRoute),
        ]);
    }

    private function saveCombatLogRouteEnemyFailures(
        MappingVersion           $mappingVersion,
        CombatLogRouteRequestDto $combatLogRoute,
        DungeonRoute             $dungeonRoute,
    ): void {
        $now               = now();
        $failureAttributes = [];

        /** @var Floor|null $previousFloor */
        $previousFloor = $dungeonRoute->dungeon->floors()->firstWhere('default', 1);

        $zeroEnemyForcesNpcIds = $this->getZeroEnemyForcesNpcIds($mappingVersion, $combatLogRoute);

        foreach ($combatLogRoute->npcs as $combatLogRouteNpc) {
            $currentFloor = $combatLogRouteNpc->getResolvedEnemy()?->floor ?? $previousFloor; // @phpstan-ignore nullsafe.neverNull

            if ($currentFloor === null) {
                continue;
            }

            // Track the floor regardless of whether a failure gets recorded below - later npcs that
            // fall back to $previousFloor must still see this npc's floor even if this one is skipped.
            $previousFloor = $currentFloor;

            if ($combatLogRouteNpc->getResolvedEnemy() === null) {
                // An npc worth 0 enemy forces in this mapping version never affects the route that gets
                // built, so failing to place it is noise rather than a mapping problem worth triaging.
                if (isset($zeroEnemyForcesNpcIds[$combatLogRouteNpc->npcId])) {
                    $this->log->saveCombatLogRouteEnemyFailuresSkippingZeroEnemyForcesNpc($dungeonRoute->id, $combatLogRouteNpc->npcId);

                    continue;
                }

                // This table is diagnostic bookkeeping only (unresolved-npc triage) - a floor with
                // unset ingame coordinates (a mapping data gap, #3904) must not fail the whole combat
                // log route submission just because it can't be recorded here.
                try {
                    $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                        new IngameXY(
                            $combatLogRouteNpc->coord->x,
                            $combatLogRouteNpc->coord->y,
                            $currentFloor,
                        ),
                    );
                } catch (InvalidArgumentException) {
                    $this->log->saveCombatLogRouteEnemyFailuresUnableToCalculateMapLocation($dungeonRoute->id, $combatLogRouteNpc->npcId, $currentFloor->id);

                    continue;
                }

                $failureAttributes[] = array_merge([
                    'dungeon_route_id'   => $dungeonRoute->id,
                    'dungeon_id'         => $dungeonRoute->dungeon_id,
                    'floor_id'           => $currentFloor->id,
                    'mapping_version_id' => $mappingVersion->id,
                    'npc_id'             => $combatLogRouteNpc->npcId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ], $latLng->toArray());
            }
        }

        if (!empty($failureAttributes)) {
            CombatLogRouteEnemyFailure::insert($failureAttributes);
        }
    }

    /**
     * The ids of the unresolved npcs in the combat log route that are explicitly worth 0 enemy forces in the given
     * mapping version, keyed by npc id. An npc without any enemy forces row at all is NOT in here - that is the
     * "this npc is not mapped" signal the enemy failure triage exists to surface.
     *
     * @return array<int, true>
     */
    private function getZeroEnemyForcesNpcIds(MappingVersion $mappingVersion, CombatLogRouteRequestDto $combatLogRoute): array
    {
        /** @var array<int, true> $unresolvedNpcIds */
        $unresolvedNpcIds = [];
        foreach ($combatLogRoute->npcs as $combatLogRouteNpc) {
            if ($combatLogRouteNpc->getResolvedEnemy() === null) {
                $unresolvedNpcIds[$combatLogRouteNpc->npcId] = true;
            }
        }

        if ($unresolvedNpcIds === []) {
            return [];
        }

        $zeroEnemyForcesNpcIds = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('npc_id', array_keys($unresolvedNpcIds))
            ->where('enemy_forces', 0)
            ->pluck('npc_id')
            ->map(static fn($npcId): int => (int)$npcId)
            ->all();

        return array_fill_keys($zeroEnemyForcesNpcIds, true);
    }

    private function generateMapIcons(
        MappingVersion           $mappingVersion,
        CombatLogRouteRequestDto $combatLogRoute,
        ?DungeonRoute            $dungeonRoute = null,
    ): void {
        $now                 = now();
        $mapIconAttributes   = [];
        $polylineAttributes  = [];
        $brushlineAttributes = [];

        /** @var \Illuminate\Database\Eloquent\Collection<int, Npc> $npcs */
        $npcs = $this->npcRepository->getInUseNpcs($dungeonRoute->mappingVersion)->keyBy('id');
        /** @var Collection<int, int> $validNpcIds */
        $validNpcIds = $this->npcRepository->getInUseNpcIds($dungeonRoute->mappingVersion);
        /** @var Floor|null $previousFloor */
        $previousFloor = $dungeonRoute->dungeon->floors()->firstWhere('default', 1);
        $latLngs       = [];
        foreach ($combatLogRoute->npcs as $combatLogRouteNpc) {
            $currentFloor = $combatLogRouteNpc->getResolvedEnemy()?->floor ?? $previousFloor; // @phpstan-ignore nullsafe.neverNull

            if ($currentFloor === null) {
                $this->log->generateMapIconsUnableToFindFloor($combatLogRouteNpc->getUniqueId());

                continue;
            }

            // A floor with unset ingame coordinates (a mapping data gap, #3904) must not fail the
            // whole request just because this one npc's icon can't be placed.
            try {
                $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                    new IngameXY(
                        $combatLogRouteNpc->coord->x,
                        $combatLogRouteNpc->coord->y,
                        $currentFloor,
                    ),
                );
            } catch (InvalidArgumentException) {
                $this->log->generateMapIconsUnableToCalculateMapLocation($combatLogRouteNpc->getUniqueId(), $currentFloor->id);

                continue;
            }
            $latLngs[] = $latLng;

            /** @var Npc|null $npc */
            $npc     = $npcs->get($combatLogRouteNpc->npcId);
            $comment = json_encode(['name' => __($npc?->name ?? 'Npc not found', [], 'en_US')] + $combatLogRouteNpc->toArray()); // @phpstan-ignore nullsafe.neverNull

            $hasResolvedEnemy = $combatLogRouteNpc->getResolvedEnemy() !== null;

            $mapIconAttributes[] = array_merge([
                'mapping_version_id' => null,
                'floor_id'           => $currentFloor->id,
                'dungeon_route_id'   => $dungeonRoute->id,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[$hasResolvedEnemy && $validNpcIds->search($combatLogRouteNpc->npcId) !== false ?
                    MapIconType::MAP_ICON_TYPE_DOT_YELLOW :
                    MapIconType::MAP_ICON_TYPE_NEONBUTTON_RED],
                'comment'           => $comment,
                'permanent_tooltip' => 0,
            ], $latLng->toArray());

            if ($hasResolvedEnemy) {
                $brushlineAttributes[] = [
                    'dungeon_route_id' => $dungeonRoute?->id ?? null, // @phpstan-ignore nullsafe.neverNull
                    'floor_id'         => $currentFloor->id,
                    'polyline_id'      => -1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                $polylineAttributes[] = [
                    'model_class'   => Brushline::class,
                    'color'         => '#f202fa',
                    'weight'        => 2,
                    'vertices_json' => json_encode([
                        $latLng->toArray(),
                        $combatLogRouteNpc->getResolvedEnemy()->getLatLng()->toArray(),
                    ]),
                ];
            }

            $previousFloor = $currentFloor;
        }

        MapIcon::insert($mapIconAttributes);
        Brushline::insert($brushlineAttributes);

        $this->mapDrawingService->drawConnections(
            $dungeonRoute,
            $latLngs,
        );

        // Assign the paths to the polylines
        $dungeonRoute->load('brushlines');

        $index = 0;
        foreach ($dungeonRoute->brushlines as $brushline) {
            $polylineAttributes[$index]['model_id'] = $brushline->id;

            $index++;
        }

        Polyline::insert($polylineAttributes);

        // Assign the polylines back to the brushlines/paths
        $polyLines = Polyline::where(static function (Builder $builder) use ($dungeonRoute) {
            $builder->whereIn('model_id', $dungeonRoute->brushlines->pluck('id'))
                ->where('model_class', Brushline::class);
        })->orderBy('id')
            ->get('id');

        $polyLineIndex = 0;
        foreach ($dungeonRoute->brushlines as $brushline) {
            $brushline->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);

            $polyLineIndex++;
        }
    }
}
