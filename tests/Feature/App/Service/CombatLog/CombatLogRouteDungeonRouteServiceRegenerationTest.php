<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

/**
 * Regenerating a combat log route (settings->publicKey set) must replace the old route with the new one while keeping
 * the old route's ChallengeModeRun (and its post_body, the only copy of the payload) attached to whichever route
 * carries the public key. #4194: the old implementation deleted the old route first and then re-found "its" run by
 * the non-unique metadata->runId, which stole another route's run and left routes without one on any failure.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteDungeonRouteService')]
final class CombatLogRouteDungeonRouteServiceRegenerationTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const FIXTURE_NAME = 'TWW/tww_s1_ara_kara_city_of_echoes_3';

    private const FIXTURE_ROOT_PATH = '../../../Controller/Api/V1/APICombatLogController/';

    private CombatLogRouteDungeonRouteServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CombatLogRouteDungeonRouteServiceInterface::class);
    }

    #[Test]
    public function convertCombatLogRouteToDungeonRoute_givenPublicKeyOfExistingCombatLogRoute_replacesRouteAndKeepsItsRun(): void
    {
        // Arrange
        $createdRouteIds = [];

        try {
            $original          = $this->service->convertCombatLogRouteToDungeonRoute($this->getCombatLogRouteRequestDto());
            $createdRouteIds[] = $original->id;

            /** @var ChallengeModeRun $originalRun */
            $originalRun = ChallengeModeRun::where('dungeon_route_id', $original->id)->firstOrFail();
            /** @var ChallengeModeRunData $originalRunData */
            $originalRunData = ChallengeModeRunData::where('challenge_mode_run_id', $originalRun->id)->firstOrFail();

            $regeneration                      = $this->getCombatLogRouteRequestDto();
            $regeneration->settings->publicKey = $original->public_key;

            // Act
            $regenerated       = $this->service->convertCombatLogRouteToDungeonRoute($regeneration);
            $createdRouteIds[] = $regenerated->id;

            // Assert
            $this->assertNotSame($original->id, $regenerated->id);
            $this->assertSame($original->public_key, $regenerated->public_key, 'The public key must carry over to the new route');
            $this->assertSame($original->public_key, DungeonRoute::findOrFail($regenerated->id)->public_key, 'The public key swap must be persisted');
            $this->assertNull(DungeonRoute::find($original->id), 'The old route must be deleted');

            $runs = ChallengeModeRun::where('dungeon_route_id', $regenerated->id)->get();
            $this->assertCount(1, $runs, 'The new route must have exactly one run');
            $this->assertSame($originalRun->id, $runs->first()->id, 'The existing run must be moved, not re-created');
            $this->assertSame(
                $originalRunData->post_body,
                ChallengeModeRunData::where('challenge_mode_run_id', $originalRun->id)->firstOrFail()->post_body,
                'The run data must survive the regeneration untouched',
            );
            $this->assertSame(0, ChallengeModeRun::where('dungeon_route_id', $original->id)->count());
        } finally {
            $this->deleteDungeonRoutes($createdRouteIds);
        }
    }

    /**
     * run_id is a client-supplied string shared by hundreds of routes in production. Regenerating one of them must not
     * touch the run of any other route that happens to carry the same run_id - the old code re-pointed the first
     * ChallengeModeRunData it found for the run_id, orphaning that route.
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
            $this->assertSame($replacement->id, $regeneratedRun->fresh()->dungeon_route_id, 'The regenerated route\'s own run must move to its replacement');
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
