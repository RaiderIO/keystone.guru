<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\RegenerateCombatLogRoute;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Season;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsCombatLogControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    /** @var array<int, int> */
    private array $createdDungeonRouteIds = [];

    /** @var array<int, int> */
    private array $createdEnemyFailureIds = [];

    private ?string $originalAdminMapFacadeStyle = null;

    private ?int $originalAdminDungeonId = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // User::forceMapFacadeStyle() is reset by ResetsMapFacadeStyleOverride at the start of
        // every request, so it can't be used to influence the actual HTTP request under test -
        // the persisted preference on the user row is what the controller actually sees. The
        // default facade style collapses floors onto the facade floor, which makes floor
        // selection assertions non-deterministic depending on the dungeon picked below.
        $admin                             = User::findOrFail(1);
        $this->originalAdminMapFacadeStyle = $admin->map_facade_style;
        $admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);

        // Dungeon::getUserOrDefaultDungeon(), used throughout this class and by the controllers
        // under test, resolves from the persisted `users.dungeon_id` column - shared, non-refreshed
        // test DB state that many other tests write. Pinning it here to a dungeon guaranteed to have
        // a second active non-facade floor (rather than reading whatever dungeon a prior test left
        // behind) is what makes `->where('facade', 0)->where('index', '!=', 1)->firstOrFail()` below
        // deterministic instead of order-dependent (#4389).
        $this->originalAdminDungeonId = $admin->dungeon_id;
        [$pinnedDungeon]              = $this->findDungeon(facadeEnabled: false, minActiveFloors: 2);
        $admin->update(['dungeon_id' => $pinnedDungeon->id]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            User::findOrFail(1)->update([
                'map_facade_style' => $this->originalAdminMapFacadeStyle,
                'dungeon_id'       => $this->originalAdminDungeonId,
            ]);
            ChallengeModeRunData::query()
                ->whereIn(
                    'challenge_mode_run_id',
                    ChallengeModeRun::query()->whereIn('dungeon_route_id', $this->createdDungeonRouteIds)->pluck('id'),
                )
                ->delete();
            ChallengeModeRun::query()->whereIn('dungeon_route_id', $this->createdDungeonRouteIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
            CombatLogRouteEnemyFailure::query()->whereIn('id', $this->createdEnemyFailureIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenAdmin_redirectsToDefaultFloor(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        $dungeon        = Dungeon::getUserOrDefaultDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view'));

        // Assert
        $response->assertRedirect(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => $defaultFloor->index,
        ]));
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenAdmin_returnsOkAfterFollowingRedirect(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        // Act
        $response = $this->followingRedirects()->get(route('admin.tools.combatlog.route.enemy_failures.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenGuest_redirectsToLogin(): void
    {
        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view'));

        // Assert
        $response->assertRedirect();
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenMappingVersionOfOtherDungeon_returns404(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        $dungeon             = Dungeon::getUserOrDefaultDungeon();
        $otherMappingVersion = MappingVersion::query()->where('dungeon_id', '!=', $dungeon->id)->firstOrFail();

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view', [
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $otherMappingVersion->id,
        ]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenMappingVersionId_rendersThatMappingVersionSelected(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        $dungeon = Dungeon::getUserOrDefaultDungeon();
        // Prefer a non-current mapping version so the assertion proves the parameter was honoured, not the default
        $mappingVersion = $dungeon->mappingVersions()->orderBy('version')->firstOrFail();

        // Act
        $response = $this->followingRedirects()->get(route('admin.tools.combatlog.route.enemy_failures.view', [
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertMatchesRegularExpression(
            sprintf('/<option value="%d"\s+selected="selected"\s*>/', $mappingVersion->id),
            $response->getContent(),
        );
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenFailuresForUnmappedNpc_listsNpcFlaggedNotMapped(): void
    {
        $createdFailureIds = [];

        try {
            // Arrange
            $this->be(User::findOrFail(1));

            $dungeon        = Dungeon::getUserOrDefaultDungeon();
            $mappingVersion = $dungeon->getCurrentMappingVersion();
            $mappedNpcIds   = $mappingVersion->enemies()->whereNotNull('npc_id')->distinct()->pluck('npc_id');
            /** @var Npc $unmappedNpc */
            $unmappedNpc = Npc::query()->whereNotIn('id', $mappedNpcIds)->firstOrFail();
            /** @var Floor $floor */
            $floor = $dungeon->floors()->where('facade', 0)->firstOrFail();

            $createdFailureIds[] = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $dungeon->id,
                'floor_id'           => $floor->id,
                'mapping_version_id' => $mappingVersion->id,
                'npc_id'             => $unmappedNpc->id,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ])->id;

            // Act
            $response = $this->followingRedirects()->get(route('admin.tools.combatlog.route.enemy_failures.view', [
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
            ]));

            // Assert — the unmapped npc is offered in the filter, with its count and the not-mapped flag
            $response->assertOk();
            $response->assertSee(sprintf('(%d) — 1 ⚠ %s', $unmappedNpc->id, __('view_common.maps.controls.combatlogrouteenemyfailures.not_mapped')));
        } finally {
            CombatLogRouteEnemyFailure::query()->whereIn('id', $createdFailureIds)->delete();
        }
    }

    #[Test]
    public function combatLogRouteEnemyFailuresFloor_givenValidFloorIndex_returnsOkForThatFloor(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        $dungeon        = Dungeon::getUserOrDefaultDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->where('index', '!=', 1)->firstOrFail();

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => $floor->index,
        ]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function combatLogRouteEnemyFailuresFloor_givenNonExistentFloorIndex_redirectsToDefaultFloor(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        $dungeon        = Dungeon::getUserOrDefaultDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => 999999,
        ]));

        // Assert
        $response->assertRedirect(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => $defaultFloor->index,
        ]));
    }

    #[Test]
    public function combatLogRouteEnemyFailuresFloor_givenNonNumericFloorIndex_behavesAsFloorIndexOne(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        // Act - a non-numeric floorIndex is treated exactly like an explicit "1", per the same
        // `!is_numeric($floorIndex)` fallback used by DungeonExploreController/DungeonRouteController
        $expectedResponse = $this->get(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => 1,
        ]));
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => 'not-a-number',
        ]));

        // Assert
        $response->assertStatus($expectedResponse->getStatusCode());
        if ($expectedResponse->isRedirect()) {
            $response->assertRedirect($expectedResponse->headers->get('Location'));
        }
    }

    #[Test]
    public function combatLogRouteEnemyFailuresFloor_givenGuest_redirectsToLogin(): void
    {
        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => 1,
        ]));

        // Assert
        $response->assertRedirect();
    }

    #[Test]
    public function combatLogRouteEnemyFailuresFloor_givenMappingVersionOfOtherDungeon_returns404(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        $dungeon             = Dungeon::getUserOrDefaultDungeon();
        $otherMappingVersion = MappingVersion::query()->where('dungeon_id', '!=', $dungeon->id)->firstOrFail();

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex'         => 1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $otherMappingVersion->id,
        ]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function getEnemyFailures_givenAdmin_returnsDungeonRoutesKey(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true);

        // Act
        $response = $this->getJson(
            route('ajax.admin.combatlogroute.enemy_failures', ['dungeon_id' => $dungeon->id, 'mapping_version_id' => $mappingVersion->id]),
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data', 'data_type', 'weight_max', 'failure_count', 'grid_size_x', 'grid_size_y', 'dungeon_routes']);
    }

    #[Test]
    public function combatlogregenerate_givenAdmin_returnsOk(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        // Act
        $response = $this->get(route('admin.tools.combatlog.regenerate.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function combatlogregeneratesubmit_givenSingleDungeon_dispatchesOnlyThatDungeonsRoutes(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $queue = Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();

        $includedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id);
        $excludedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(1), $season->id);

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
        ]);

        // Assert
        $response->assertOk();

        $dispatchedDungeonRouteIds = $this->getDispatchedDungeonRouteIds($queue);
        $this->assertContains($includedDungeonRoute->id, $dispatchedDungeonRouteIds);
        $this->assertNotContains($excludedDungeonRoute->id, $dispatchedDungeonRouteIds);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenSeasonDungeonSelection_dispatchesEveryDungeonOfThatSeason(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $queue = Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();

        $firstDungeonRoute  = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id);
        $secondDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(1), $season->id);

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => sprintf('season-%d', $season->id),
        ]);

        // Assert
        $response->assertOk();

        $dispatchedDungeonRouteIds = $this->getDispatchedDungeonRouteIds($queue);
        $this->assertContains($firstDungeonRoute->id, $dispatchedDungeonRouteIds);
        $this->assertContains($secondDungeonRoute->id, $dispatchedDungeonRouteIds);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenSeasonId_dispatchesOnlyRoutesCreatedInThatSeason(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $queue = Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();
        $otherSeason         = Season::query()->where('id', '!=', $season->id)->orderByDesc('id')->firstOrFail();

        $includedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id);
        $excludedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $otherSeason->id);

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
            'season_id'  => $season->id,
        ]);

        // Assert
        $response->assertOk();

        $dispatchedDungeonRouteIds = $this->getDispatchedDungeonRouteIds($queue);
        $this->assertContains($includedDungeonRoute->id, $dispatchedDungeonRouteIds);
        $this->assertNotContains($excludedDungeonRoute->id, $dispatchedDungeonRouteIds);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenPeriods_dispatchesOnlyRoutesRunInThoseWeeks(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $queue = Queue::fake();

        [$season, $periods, $dungeons] = $this->findSeasonWithWeeklyPeriods();

        $includedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id, $periods->first());
        $excludedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id, $periods->get(1));

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
            'periods'    => [$periods->first()],
        ]);

        // Assert
        $response->assertOk();

        $dispatchedDungeonRouteIds = $this->getDispatchedDungeonRouteIds($queue);
        $this->assertContains($includedDungeonRoute->id, $dispatchedDungeonRouteIds);
        $this->assertNotContains($excludedDungeonRoute->id, $dispatchedDungeonRouteIds);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenPeriodsAndRunWithoutPostBody_doesNotDispatchThatRun(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $queue = Queue::fake();

        [$season, $periods, $dungeons] = $this->findSeasonWithWeeklyPeriods();

        $prunedDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id);

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
            'periods'    => [$periods->first()],
        ]);

        // Assert
        $response->assertOk();

        $this->assertNotContains($prunedDungeonRoute->id, $this->getDispatchedDungeonRouteIds($queue));
    }

    #[Test]
    public function combatlogregeneratesubmit_givenNoPeriods_dispatchesRunsOfEveryWeek(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $queue = Queue::fake();

        [$season, $periods, $dungeons] = $this->findSeasonWithWeeklyPeriods();

        $firstDungeonRoute  = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id, $periods->first());
        $secondDungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeons->get(0), $season->id, $periods->get(1));

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
        ]);

        // Assert
        $response->assertOk();

        $dispatchedDungeonRouteIds = $this->getDispatchedDungeonRouteIds($queue);
        $this->assertContains($firstDungeonRoute->id, $dispatchedDungeonRouteIds);
        $this->assertContains($secondDungeonRoute->id, $dispatchedDungeonRouteIds);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenPeriodOutsideSelectedSeason_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [$season, $periods, $dungeons] = $this->findSeasonWithWeeklyPeriods();

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
            'season_id'  => $season->id,
            'periods'    => [$periods->max() + 1],
        ]);

        // Assert
        $response->assertSessionHasErrors('periods');
    }

    #[Test]
    public function combatlogregeneratesubmit_givenPeriodInsideSelectedSeason_passesValidation(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [$season, $periods, $dungeons] = $this->findSeasonWithWeeklyPeriods();

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
            'season_id'  => $season->id,
            'periods'    => [$periods->first()],
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $response->assertOk();
    }

    #[Test]
    public function combatlogregeneratesubmit_givenNonNumericPeriod_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [, $dungeons] = $this->findSeasonWithDungeons();

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
            'periods'    => ['not-a-period'],
        ]);

        // Assert
        $response->assertSessionHasErrors('periods.0');
    }

    #[Test]
    public function combatlogregeneratesubmit_givenUnknownSeasonId_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => -1,
            'season_id'  => 999999,
        ]);

        // Assert
        $response->assertSessionHasErrors('season_id');
        Queue::assertNotPushed(RegenerateCombatLogRoute::class);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenUnknownDungeonId_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => 999999,
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_id');
        Queue::assertNotPushed(RegenerateCombatLogRoute::class);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenUnknownSeasonInDungeonSelection_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => 'season-999999',
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_id');
        Queue::assertNotPushed(RegenerateCombatLogRoute::class);
    }

    #[Test]
    public function combatlogregeneratesubmit_givenDeleteEnemyFailuresUnchecked_leavesFailuresUntouched(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();
        $failure             = $this->createEnemyFailureForDungeon($dungeons->get(0));

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id' => $dungeons->get(0)->id,
        ]);

        // Assert
        $response->assertOk();
        $this->assertTrue(CombatLogRouteEnemyFailure::query()->whereKey($failure->id)->exists());
    }

    #[Test]
    public function combatlogregeneratesubmit_givenDeleteEnemyFailuresCheckedWithSingleDungeon_deletesOnlyThatDungeonsFailures(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();
        $includedFailure     = $this->createEnemyFailureForDungeon($dungeons->get(0));
        $excludedFailure     = $this->createEnemyFailureForDungeon($dungeons->get(1));

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id'            => $dungeons->get(0)->id,
            'delete_enemy_failures' => 1,
        ]);

        // Assert
        $response->assertOk();
        $this->assertFalse(CombatLogRouteEnemyFailure::query()->whereKey($includedFailure->id)->exists());
        $this->assertTrue(CombatLogRouteEnemyFailure::query()->whereKey($excludedFailure->id)->exists());
    }

    #[Test]
    public function combatlogregeneratesubmit_givenDeleteEnemyFailuresCheckedWithAllDungeons_deletesFailuresForEveryDungeon(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();
        $firstFailure        = $this->createEnemyFailureForDungeon($dungeons->get(0));
        $secondFailure       = $this->createEnemyFailureForDungeon($dungeons->get(1));

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id'            => '-1',
            'delete_enemy_failures' => 1,
        ]);

        // Assert
        $response->assertOk();
        $this->assertFalse(CombatLogRouteEnemyFailure::query()->whereKey($firstFailure->id)->exists());
        $this->assertFalse(CombatLogRouteEnemyFailure::query()->whereKey($secondFailure->id)->exists());
    }

    #[Test]
    public function combatlogregeneratesubmit_givenDeleteEnemyFailuresCheckedWithSeasonDungeonSelection_deletesOnlyThatSeasonsDungeonsFailures(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        Queue::fake();

        [$season, $dungeons] = $this->findSeasonWithDungeons();
        $otherDungeon        = Dungeon::query()
            ->whereNotIn('id', $dungeons->pluck('id'))
            ->get()
            ->filter(static fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null)
            ->first();
        $this->assertNotNull($otherDungeon, 'Unable to find a dungeon outside the chosen season to prove scoping!');

        $includedFailure = $this->createEnemyFailureForDungeon($dungeons->get(0));
        $excludedFailure = $this->createEnemyFailureForDungeon($otherDungeon);

        // Act
        $response = $this->post(route('admin.tools.combatlog.regenerate.submit'), [
            'dungeon_id'            => sprintf('season-%d', $season->id),
            'delete_enemy_failures' => 1,
        ]);

        // Assert
        $response->assertOk();
        $this->assertFalse(CombatLogRouteEnemyFailure::query()->whereKey($includedFailure->id)->exists());
        $this->assertTrue(CombatLogRouteEnemyFailure::query()->whereKey($excludedFailure->id)->exists());
    }

    /**
     * A season that has at least two dungeons which can hold a dungeon route, together with those dungeons.
     *
     * @return array{0: Season, 1: Collection<int, Dungeon>}
     */
    private function findSeasonWithDungeons(): array
    {
        /** @var Collection<int, Season> $seasons */
        $seasons = Season::with(['dungeons'])->orderByDesc('id')->get();

        foreach ($seasons as $season) {
            $dungeons = $season->dungeons
                ->filter(static fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null)
                ->values();

            if ($dungeons->count() >= 2) {
                return [$season, $dungeons];
            }
        }

        $this->fail('Unable to find a season with at least two dungeons to create dungeon routes for!');
    }

    /**
     * @param int|null $period The leaderboard period to store in the run's post body - null creates no run data
     *                         at all, mimicking a run whose post body was pruned.
     */
    private function createDungeonRouteWithChallengeModeRun(Dungeon $dungeon, int $seasonId, ?int $period = null): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'season_id'          => $seasonId,
        ]);

        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $dungeon->id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => 10,
            'success'          => 1,
            'total_time_ms'    => 1000,
            'duplicate'        => 0,
        ]);

        if ($period !== null) {
            ChallengeModeRunData::create([
                'challenge_mode_run_id' => $challengeModeRun->id,
                'run_id'                => sprintf('run-%d', $challengeModeRun->id),
                'correlation_id'        => sprintf('correlation-%d', $challengeModeRun->id),
                'post_body'             => json_encode(['metadata' => ['period' => $period]]),
                'processed'             => 0,
            ]);
        }

        return $dungeonRoute;
    }

    /**
     * A season that has at least two weeks behind it, together with the leaderboard periods of those weeks.
     *
     * @return array{0: Season, 1: Collection<int, int>, 2: Collection<int, Dungeon>}
     */
    private function findSeasonWithWeeklyPeriods(): array
    {
        $seasonService = app(SeasonServiceInterface::class);
        $region        = GameServerRegion::query()->where('short', GameServerRegion::DEFAULT_REGION)->firstOrFail();

        /** @var Collection<int, Season> $seasons */
        $seasons = Season::with(['dungeons'])->orderByDesc('start')->get();

        foreach ($seasons as $season) {
            $dungeons = $season->dungeons
                ->filter(static fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null)
                ->values();

            $periods = $seasonService->getWeeklyPeriods($season, $region)->values();

            if ($dungeons->isNotEmpty() && $periods->count() >= 2) {
                return [$season, $periods, $dungeons];
            }
        }

        $this->fail('Unable to find a season with at least two weeks and a dungeon to create dungeon routes for!');
    }

    private function createEnemyFailureForDungeon(Dungeon $dungeon): CombatLogRouteEnemyFailure
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->firstOrFail();
        /** @var Npc $npc */
        $npc = Npc::query()->firstOrFail();

        $failure = CombatLogRouteEnemyFailure::create([
            'dungeon_id'         => $dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => $npc->id,
            'lat'                => -50.0,
            'lng'                => 100.0,
        ]);

        $this->createdEnemyFailureIds[] = $failure->id;

        return $failure;
    }

    /**
     * The dungeon route ids of every RegenerateCombatLogRoute job that was pushed onto the faked queue.
     *
     * @return array<int, int>
     */
    private function getDispatchedDungeonRouteIds(QueueFake $queue): array
    {
        return $queue->pushed(RegenerateCombatLogRoute::class)
            ->map(static function (RegenerateCombatLogRoute $job): int {
                $property = new ReflectionProperty($job, 'dungeonRouteId');

                return (int)$property->getValue($job);
            })
            ->toArray();
    }
}
