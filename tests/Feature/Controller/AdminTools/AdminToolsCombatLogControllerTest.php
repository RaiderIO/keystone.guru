<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\RegenerateCombatLogRoute;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Models\User;
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

    #[\Override]
    protected function tearDown(): void
    {
        try {
            ChallengeModeRun::query()->whereIn('dungeon_route_id', $this->createdDungeonRouteIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenAdmin_returnsOk(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view'));

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
    public function getEnemyFailures_givenAdmin_returnsDungeonRoutesKey(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        [$dungeon] = $this->findDungeon(challengeMode: true);

        // Act
        $response = $this->getJson(
            route('ajax.admin.combatlogroute.enemy_failures', ['dungeon_id' => $dungeon->id]),
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

    private function createDungeonRouteWithChallengeModeRun(Dungeon $dungeon, int $seasonId): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'season_id'          => $seasonId,
        ]);

        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        ChallengeModeRun::create([
            'dungeon_id'       => $dungeon->id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => 10,
            'success'          => 1,
            'total_time_ms'    => 1000,
            'duplicate'        => 0,
        ]);

        return $dungeonRoute;
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
