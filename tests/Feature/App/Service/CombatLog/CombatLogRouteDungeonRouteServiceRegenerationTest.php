<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteCoordRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\Exceptions\CombatLogRouteRegeneratedConcurrentlyException;
use App\Service\DungeonRoute\DungeonRouteUpgradeDraftServiceInterface;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftGoneException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

/**
 * Regenerating a combat log route (settings->publicKey set) replaces that route's *contents* while the route itself -
 * its id, its public key and its ChallengeModeRun (whose post_body is the only copy of the payload) - stays exactly
 * where it was. #4297 moved this onto the draft-and-apply path: the combat log builds an upgrade draft, and applying
 * it writes the draft's content onto the original. Before that, regeneration built a whole new row and handed it the
 * old public key, which changed the id and dropped every relation it did not explicitly carry over.
 *
 * #4194 still applies underneath: the original is only touched once the draft is complete, so a failure halfway
 * leaves it exactly as it was.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteDungeonRouteService')]
final class CombatLogRouteDungeonRouteServiceRegenerationTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const FIXTURE_NAME = 'TWW/tww_s1_ara_kara_city_of_echoes_3';

    private const FIXTURE_ROOT_PATH = '../../../Controller/Api/V1/APICombatLogController/';

    /** @var int An npc id that resolves to no enemy and carries no enemy forces, so it is recorded as a failure. */
    private const UNRESOLVABLE_NPC_ID = 999999999;

    private CombatLogRouteDungeonRouteServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CombatLogRouteDungeonRouteServiceInterface::class);
    }

    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenPublicKeyOfExistingCombatLogRoute_replacesContentsAndKeepsRouteIdentity(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;

            /** @var ChallengeModeRun $originalRun */
            $originalRun = ChallengeModeRun::where('dungeon_route_id', $original->id)->firstOrFail();
            /** @var ChallengeModeRunData $originalRunData */
            $originalRunData     = ChallengeModeRunData::where('challenge_mode_run_id', $originalRun->id)->firstOrFail();
            $originalKillZoneIds = KillZone::where('dungeon_route_id', $original->id)->pluck('id');
            $this->assertNotEmpty($originalKillZoneIds, 'Precondition: the original must have pulls to replace');

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            // Act
            $regenerated = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);

            // Assert
            $this->assertSame($original->id, $regenerated->id, 'The route id must survive the regeneration');
            $this->assertSame($original->public_key, $regenerated->public_key, 'The public key must survive the regeneration');
            $this->assertNotNull(DungeonRoute::find($original->id), 'The route must not be replaced by a different row');
            $this->assertNull($regenerated->upgrade_of_dungeon_route_id, 'The applied route must not be left looking like a draft');
            $this->assertNull(
                DungeonRoute::where('upgrade_of_dungeon_route_id', $original->id)->first(),
                'No upgrade draft may be left behind once the regeneration applied',
            );

            $newKillZones = KillZone::where('dungeon_route_id', $original->id)->get();
            $this->assertNotEmpty($newKillZones, 'The regenerated route must have pulls');
            $this->assertEmpty(
                $originalKillZoneIds->intersect($newKillZones->pluck('id')),
                'The pulls must be the freshly built ones, not the previous generation\'s',
            );
            $this->assertNotEmpty(
                KillZoneEnemy::whereIn('kill_zone_id', $newKillZones->pluck('id'))->get(),
                'The pulls must carry their enemies - kill zone children are cloned onto the original, not left on the draft',
            );

            $runs = ChallengeModeRun::where('dungeon_route_id', $original->id)->get();
            $this->assertCount(1, $runs, 'The route must still have exactly one run');
            $this->assertSame($originalRun->id, $runs->first()->id, 'The existing run must be left alone, not re-created or moved');
            $this->assertSame(
                $originalRunData->post_body,
                ChallengeModeRunData::where('challenge_mode_run_id', $originalRun->id)->firstOrFail()->post_body,
                'The run data must survive the regeneration untouched',
            );
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * The whole point of #4297: everything hanging off the route that is not its content survives a regeneration,
     * because the route keeps its id. The pre-#4297 replacement deleted the row and took all of this with it.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenRegenerationOfARouteWithAnAudience_preservesThatAudience(): void
    {
        // Arrange
        $createdRouteIds = [];
        $user            = User::factory()->create();

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;

            DungeonRoute::query()->whereKey($original->id)->update([
                'views'        => 1234,
                'popularity'   => 56,
                'rating'       => 4.5,
                'rating_count' => 7,
            ]);
            DungeonRouteFavorite::create(['dungeon_route_id' => $original->id, 'user_id' => $user->id]);
            UserPinnedDungeonRoute::create(['dungeon_route_id' => $original->id, 'user_id' => $user->id, 'order' => 0]);

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            // Act
            $regenerated = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);

            // Assert
            $this->assertSame(1234, $regenerated->views);
            $this->assertSame(56, $regenerated->popularity);
            $this->assertSame(4.5, (float)$regenerated->rating);
            $this->assertSame(7, $regenerated->rating_count);
            $this->assertSame(1, DungeonRouteFavorite::where('dungeon_route_id', $original->id)->count(), 'Favorites must survive');
            $this->assertSame(1, UserPinnedDungeonRoute::where('dungeon_route_id', $original->id)->count(), 'Pins must survive');
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
            DungeonRouteFavorite::where('user_id', $user->id)->delete();
            UserPinnedDungeonRoute::where('user_id', $user->id)->delete();
            $user->delete();
        }
    }

    /**
     * An upgrade draft found on the route being regenerated is the wreckage of an earlier ARC run that died between
     * creating its draft and applying it. Only one draft may exist per route, so leaving it would block every future
     * regeneration - it is taken over instead.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenAbandonedUpgradeDraftOnTheRoute_takesItOverAndLeavesNoneBehind(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;

            $abandonedDraft = DungeonRoute::create([
                'public_key'                  => DungeonRoute::generateRandomPublicKey(),
                'upgrade_of_dungeon_route_id' => $original->id,
                'author_id'                   => $original->author_id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'title'                       => $original->title,
            ]);
            $createdRouteIds[] = $abandonedDraft->id;

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            // Act
            $regenerated = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);

            // Assert
            $this->assertSame($original->id, $regenerated->id);
            $this->assertNull(DungeonRoute::find($abandonedDraft->id), 'The abandoned draft must be discarded');
            $this->assertNull(
                DungeonRoute::where('upgrade_of_dungeon_route_id', $original->id)->first(),
                'No upgrade draft may be left behind once the regeneration applied',
            );
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * Enemy failures are recorded per generation and live on the combatlog connection, outside the content apply()
     * moves. They must end up on the route that survives, and must not pile up generation after generation - the
     * pre-#4297 path got away with never cleaning them because it deleted the whole route each time.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenRegeneration_movesEnemyFailuresOntoTheRouteAndDropsThePreviousOnes(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;

            // This fixture resolves every npc, so the previous generation's failure is planted by hand
            $previousFailure = CombatLogRouteEnemyFailure::create([
                'dungeon_route_id'   => $original->id,
                'dungeon_id'         => $original->dungeon_id,
                'floor_id'           => $original->dungeon->floors()->firstWhere('default', 1)->id,
                'mapping_version_id' => $original->mapping_version_id,
                'npc_id'             => self::UNRESOLVABLE_NPC_ID,
                'lat'                => 1.0,
                'lng'                => 1.0,
            ]);

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;
            // An npc that resolves to no enemy at all is what this table records
            $regeneration->npcs->push(new CombatLogRouteNpcRequestDto(
                npcId: self::UNRESOLVABLE_NPC_ID,
                coord: new CombatLogRouteCoordRequestDto(1.0, 1.0),
            ));

            // Act
            $regenerated = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);

            // Assert
            $failuresAfter = CombatLogRouteEnemyFailure::where('dungeon_route_id', $regenerated->id)->pluck('id');
            $this->assertNotEmpty($failuresAfter, 'The regeneration\'s own enemy failures must end up on the route');
            $this->assertNotContains(
                $previousFailure->id,
                $failuresAfter,
                'The previous generation\'s enemy failures must be dropped, not accumulated',
            );
            $this->assertNull(CombatLogRouteEnemyFailure::find($previousFailure->id), 'The previous failures must be deleted, not orphaned');
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * run_id is a client-supplied string shared by hundreds of routes in production. Regenerating one of them must not
     * touch the run of any other route that happens to carry the same run_id - the old code re-pointed the first
     * ChallengeModeRunData it found for the run_id, orphaning that route. Since #4297 the run does not move at all,
     * which makes the bystander's run safe by construction - asserted anyway, the guard costs nothing.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenAnotherRouteSharingTheRunId_doesNotStealThatRoutesRun(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $bystander         = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $bystander->id;
            $regenerated       = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $regenerated->id;

            /** @var ChallengeModeRun $bystanderRun */
            $bystanderRun = ChallengeModeRun::where('dungeon_route_id', $bystander->id)->firstOrFail();
            /** @var ChallengeModeRun $regeneratedRun */
            $regeneratedRun = ChallengeModeRun::where('dungeon_route_id', $regenerated->id)->firstOrFail();
            $this->assertSame(
                $bystanderRun->challengeModeRunData->run_id,
                $regeneratedRun->challengeModeRunData->run_id,
                'Precondition: both routes share the same run_id',
            );

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $regenerated->public_key;

            // Act
            $replacement       = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);
            $createdRouteIds[] = $replacement->id;

            // Assert
            $this->assertSame($bystander->id, $bystanderRun->fresh()->dungeon_route_id, 'The bystander must keep its run');
            $this->assertSame(1, ChallengeModeRun::where('dungeon_route_id', $bystander->id)->count());
            $this->assertSame($regenerated->id, $replacement->id, 'A regeneration replaces the route\'s contents in place');
            $this->assertSame($regenerated->id, $regeneratedRun->fresh()->dungeon_route_id, 'The regenerated route\'s own run must stay where it is');
            $this->assertSame(1, ChallengeModeRun::where('dungeon_route_id', $replacement->id)->count());
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * Only routes that were generated by ARC (i.e. have a ChallengeModeRun) may be replaced by public key - this is
     * what stops the API from overwriting arbitrary routes.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenPublicKeyOfRouteWithoutRun_leavesThatRouteAloneAndCreatesANewOne(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $routeWithoutRun   = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $routeWithoutRun->id;
            ChallengeModeRun::where('dungeon_route_id', $routeWithoutRun->id)->first()->delete();

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $routeWithoutRun->public_key;

            // Act
            $newRoute          = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);
            $createdRouteIds[] = $newRoute->id;

            // Assert
            $this->assertNotNull(DungeonRoute::find($routeWithoutRun->id), 'The route without a run must not be deleted');
            $this->assertNotSame($routeWithoutRun->public_key, $newRoute->public_key, 'The new route must not take over the public key');
            $this->assertSame(1, ChallengeModeRun::where('dungeon_route_id', $newRoute->id)->count());
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * The old route is only replaced once the new one has been built completely: a failure halfway leaves the old
     * route and its run (with the post_body, the only copy of the payload) exactly as they were, so the regeneration
     * can be retried. The half-built new route is cleaned up.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenBuildFailureDuringRegeneration_leavesExistingRouteAndRunIntact(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;
            /** @var ChallengeModeRun $originalRun */
            $originalRun = ChallengeModeRun::where('dungeon_route_id', $original->id)->firstOrFail();

            $killZoneRepository = $this->createMock(KillZoneRepositoryInterface::class);
            $killZoneRepository->method('create')->willThrowException(new RuntimeException('Simulated build failure'));
            $this->app->instance(KillZoneRepositoryInterface::class, $killZoneRepository);
            /** @var CombatLogRouteDungeonRouteServiceInterface $failingService */
            $failingService = app(CombatLogRouteDungeonRouteServiceInterface::class);

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            $routeCountBefore = DungeonRoute::count();

            // Act
            $thrown = null;

            try {
                $failingService->convertCombatLogRouteToDungeonRoute($regeneration);
            } catch (RuntimeException $runtimeException) {
                $thrown = $runtimeException;
            }

            // Assert
            $this->assertNotNull($thrown, 'The failure must be propagated to the caller');
            $this->assertSame('Simulated build failure', $thrown->getMessage());
            $this->assertNotNull(DungeonRoute::find($original->id), 'The original route must survive a failed regeneration');
            $this->assertSame($original->public_key, DungeonRoute::findOrFail($original->id)->public_key);
            $this->assertSame($original->id, $originalRun->fresh()->dungeon_route_id, 'The run must still point at the original route');
            $this->assertNotNull(ChallengeModeRunData::where('challenge_mode_run_id', $originalRun->id)->first(), 'The run data must survive');
            $this->assertSame($routeCountBefore, DungeonRoute::count(), 'The half-built route must be cleaned up');
            $this->assertNull(
                DungeonRoute::where('upgrade_of_dungeon_route_id', $original->id)->first(),
                'The failed regeneration must not leave its draft behind either',
            );
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * Two regenerations of the same route that overlap must not both build into the same draft. The winner is
     * decided by dungeon_routes_upgrade_of_unique: whoever claims the draft first keeps it, and the loser is told
     * so before it does any work rather than after.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenDraftClaimedByConcurrentRegeneration_throwsWithoutBuilding(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;
            /** @var ChallengeModeRun $originalRun */
            $originalRun = ChallengeModeRun::where('dungeon_route_id', $original->id)->firstOrFail();

            // Let the concurrent regeneration claim the draft in the window between this one discarding whatever
            // draft it found and claiming its own - i.e. while the builder is still constructing itself
            $realEnemyRepository = app(EnemyRepositoryInterface::class);
            $enemyRepository     = $this->createMock(EnemyRepositoryInterface::class);
            $enemyRepository->method('getAvailableEnemiesForDungeonRouteBuilder')->willReturnCallback(
                function (MappingVersion $mappingVersion) use ($realEnemyRepository, $original, &$createdRouteIds) {
                    if (DungeonRoute::where('upgrade_of_dungeon_route_id', $original->id)->doesntExist()) {
                        $createdRouteIds[] = DungeonRoute::create([
                            'public_key'                  => DungeonRoute::generateRandomPublicKey(),
                            'upgrade_of_dungeon_route_id' => $original->id,
                            'author_id'                   => $original->author_id,
                            'dungeon_id'                  => $original->dungeon_id,
                            'mapping_version_id'          => $original->mapping_version_id,
                            'title'                       => $original->title,
                        ])->id;
                    }

                    return $realEnemyRepository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);
                },
            );
            $this->app->instance(EnemyRepositoryInterface::class, $enemyRepository);
            /** @var CombatLogRouteDungeonRouteServiceInterface $losingService */
            $losingService = app(CombatLogRouteDungeonRouteServiceInterface::class);

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            // Act
            $thrown = null;

            try {
                $losingService->convertCombatLogRouteToDungeonRoute($regeneration);
            } catch (CombatLogRouteRegeneratedConcurrentlyException $concurrentlyException) {
                $thrown = $concurrentlyException;
            }

            // Assert
            $this->assertNotNull($thrown, 'Losing the race for the draft must be reported to the caller');
            $this->assertSame($original->id, $originalRun->fresh()->dungeon_route_id, 'The run must stay where it was');
            $this->assertNotNull(DungeonRoute::find($original->id), 'The loser must leave the route it lost alone');
            $this->assertSame(
                1,
                DungeonRoute::where('upgrade_of_dungeon_route_id', $original->id)->count(),
                'The winner\'s draft must be the only one, and must survive the loser',
            );
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * The other half of the race: this regeneration claimed the draft, but a later one took it over while this one
     * was still building. Applying it must fail rather than resurrect the draft, and this regeneration must clean up
     * the content it wrote against the taken-over draft rather than orphan it.
     */
    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenDraftTakenOverDuringBuild_discardsOwnDraftAndThrows(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;
            /** @var ChallengeModeRun $originalRun */
            $originalRun = ChallengeModeRun::where('dungeon_route_id', $original->id)->firstOrFail();

            // Stands in for the draft having been taken over by the time this regeneration got to apply it
            $upgradeDraftService = $this->createMock(DungeonRouteUpgradeDraftServiceInterface::class);
            $upgradeDraftService->method('apply')->willThrowException(new UpgradeDraftGoneException('Taken over'));
            $this->app->instance(DungeonRouteUpgradeDraftServiceInterface::class, $upgradeDraftService);
            /** @var CombatLogRouteDungeonRouteServiceInterface $losingService */
            $losingService = app(CombatLogRouteDungeonRouteServiceInterface::class);

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            $routeCountBefore    = DungeonRoute::count();
            $killZoneCountBefore = KillZone::count();

            // Act
            $thrown = null;

            try {
                $losingService->convertCombatLogRouteToDungeonRoute($regeneration);
            } catch (CombatLogRouteRegeneratedConcurrentlyException $concurrentlyException) {
                $thrown = $concurrentlyException;
            }

            // Assert
            $this->assertNotNull($thrown, 'Losing the race must be reported to the caller');
            $this->assertNotNull(DungeonRoute::find($original->id), 'The loser must not touch the route it lost');
            $this->assertSame($original->id, $originalRun->fresh()->dungeon_route_id, 'The run must stay where it was');
            $this->assertNotNull(ChallengeModeRunData::where('challenge_mode_run_id', $originalRun->id)->first(), 'The run data must survive');
            $this->assertSame($routeCountBefore, DungeonRoute::count(), 'The loser\'s draft must be discarded');
            $this->assertSame($killZoneCountBefore, KillZone::count(), 'The loser\'s pulls must be cleaned up, not orphaned');
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    private function getCombatLogRouteRequestDto(): CombatLogRouteRequestDto
    {
        return CombatLogRouteRequestDto::createFromArray(
            $this->getJsonData(self::FIXTURE_NAME, self::FIXTURE_ROOT_PATH),
        );
    }

    /**
     * @param array<int, int> $dungeonRouteIds
     */
    private function deleteDungeonRoutes(array $dungeonRouteIds): void
    {
        foreach ($dungeonRouteIds as $dungeonRouteId) {
            // Model delete so DungeonRoute::deleting cascades into the run + run data
            DungeonRoute::find($dungeonRouteId)?->delete();

            // Belt and braces for runs left dangling by a failing assertion
            $challengeModeRunIds = ChallengeModeRun::where('dungeon_route_id', $dungeonRouteId)->pluck('id');
            ChallengeModeRunData::whereIn('challenge_mode_run_id', $challengeModeRunIds)->delete();
            ChallengeModeRun::whereIn('id', $challengeModeRunIds)->delete();
        }
    }
}
