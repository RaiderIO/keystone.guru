<?php

namespace Tests\Feature\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\DungeonRoute\PublishScheduled;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteScheduledPublish;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
final class PublishScheduledTest extends PublicTestCase
{
    use CreatesDungeon;
    use ProvidesDungeon;

    private DungeonRoute $dungeonRoute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->make([
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);

        $this->dungeonRoute = $dungeonRoute;
        $this->dungeonRoute->save();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
            $this->dungeonRoute->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenDueSchedule_publishesRouteAndDeletesRecord(): void
    {
        // Arrange — an unpublished route on a mapping version without required enemies, so nothing but the
        // schedule decides its published state
        $route = $this->createTeamRouteOnActiveDungeon(
            withRequiredEnemies: false,
            attributes: ['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]],
        );
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $route->id,
            'published_state'  => PublishedState::TEAM,
            'publish_at'       => Carbon::now()->subMinute(),
        ]);

        try {
            // Act
            $this->artisan(PublishScheduled::class)->assertSuccessful();

            // Assert
            $route->refresh();
            $this->assertEquals(PublishedState::ALL[PublishedState::TEAM], $route->published_state_id);
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $route->id,
            ]);
        } finally {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $route->id)->delete();
            $route->delete();
        }
    }

    #[Test]
    public function handle_givenDueScheduleForWorldState_setsPublishedAt(): void
    {
        // Arrange — a route on an active Mythic+ dungeon. The route has no pulls, so a mapping version with required
        // enemies would refuse the publish
        [$activeDungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            dungeonActive: true,
            resolve: static fn(Dungeon $dungeon, MappingVersion $mappingVersion): ?bool => $mappingVersion->enemies()->where('required', true)->exists() ? null : true,
        );

        /** @var DungeonRoute $worldRoute */
        $worldRoute = DungeonRoute::factory()->make([
            'dungeon_id'         => $activeDungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
            'published_at'       => Carbon::now()->subYear(),
        ]);
        $worldRoute->save();

        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $worldRoute->id,
            'published_state'  => PublishedState::WORLD,
            'publish_at'       => Carbon::now()->subMinute(),
        ]);

        try {
            // Act
            $this->artisan(PublishScheduled::class)->assertSuccessful();

            // Assert
            $worldRoute->refresh();
            $this->assertEquals(PublishedState::ALL[PublishedState::WORLD], $worldRoute->published_state_id);
            $this->assertTrue($worldRoute->published_at->isAfter(Carbon::now()->subMinute()), 'Publishing to world must stamp published_at');
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $worldRoute->id,
            ]);
        } finally {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $worldRoute->id)->delete();
            $worldRoute->delete();
        }
    }

    #[Test]
    public function handle_givenFutureSchedule_doesNotPublishRoute(): void
    {
        // Arrange
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'published_state'  => PublishedState::WORLD,
            'publish_at'       => Carbon::now()->addHour(),
        ]);

        // Act
        $this->artisan(PublishScheduled::class)->assertSuccessful();

        // Assert
        $this->dungeonRoute->refresh();
        $this->assertEquals(PublishedState::ALL[PublishedState::TEAM], $this->dungeonRoute->published_state_id);
        $this->assertDatabaseHas('dungeon_route_scheduled_publishes', [
            'dungeon_route_id' => $this->dungeonRoute->id,
        ]);
    }

    #[Test]
    public function handle_givenWorldScheduleForInactiveDungeon_skipsAndDeletesRecord(): void
    {
        // Arrange — a route on a dungeon of our own that is inactive; the seed has none
        $inactiveDungeon = $this->createDungeon(['active' => false]);

        /** @var DungeonRoute $inactiveRoute */
        $inactiveRoute = DungeonRoute::factory()->make([
            'dungeon_id'         => $inactiveDungeon->id,
            'mapping_version_id' => $inactiveDungeon->getCurrentMappingVersion()->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);
        $inactiveRoute->save();

        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $inactiveRoute->id,
            'published_state'  => PublishedState::WORLD,
            'publish_at'       => Carbon::now()->subMinute(),
        ]);

        try {
            // Act
            $this->artisan(PublishScheduled::class)->assertSuccessful();

            // Assert — route was not published, record was deleted
            $inactiveRoute->refresh();
            $this->assertEquals(PublishedState::ALL[PublishedState::TEAM], $inactiveRoute->published_state_id);
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $inactiveRoute->id,
            ]);
        } finally {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $inactiveRoute->id)->delete();
            $inactiveRoute->delete();
        }
    }

    #[Test]
    public function handle_givenWorldScheduleForRouteMissingRequiredEnemies_skipsAndDeletesRecord(): void
    {
        // Arrange — a route without pulls on a mapping version that has required enemies
        $route = $this->createTeamRouteOnActiveDungeon(withRequiredEnemies: true);
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $route->id,
            'published_state'  => PublishedState::WORLD,
            'publish_at'       => Carbon::now()->subMinute(),
        ]);

        try {
            // Act
            $this->artisan(PublishScheduled::class)->assertSuccessful();

            // Assert
            $route->refresh();
            $this->assertEquals(PublishedState::ALL[PublishedState::TEAM], $route->published_state_id);
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $route->id,
            ]);
        } finally {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $route->id)->delete();
            $route->delete();
        }
    }

    #[Test]
    public function handle_givenWorldWithLinkScheduleByAuthorWithoutPatreonBenefit_skipsAndDeletesRecord(): void
    {
        // Arrange
        $author = User::factory()->create();
        $route  = $this->createTeamRouteOnActiveDungeon(withRequiredEnemies: false, attributes: ['author_id' => $author->id]);
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $route->id,
            'published_state'  => PublishedState::WORLD_WITH_LINK,
            'publish_at'       => Carbon::now()->subMinute(),
        ]);

        try {
            // Act
            $this->artisan(PublishScheduled::class)->assertSuccessful();

            // Assert
            $route->refresh();
            $this->assertEquals(PublishedState::ALL[PublishedState::TEAM], $route->published_state_id);
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $route->id,
            ]);
        } finally {
            DungeonRouteScheduledPublish::where('dungeon_route_id', $route->id)->delete();
            $route->delete();
            $author->delete();
        }
    }

    #[Test]
    public function handle_givenWorldScheduleForUpgradeDraft_skipsAndDeletesRecord(): void
    {
        // Arrange
        $original = $this->createTeamRouteOnActiveDungeon(withRequiredEnemies: false);
        $draft    = null;

        try {
            $draft = DungeonRoute::factory()->create([
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
                'published_at'                => Carbon::now()->subYear(),
            ]);
            DungeonRouteScheduledPublish::create([
                'dungeon_route_id' => $draft->id,
                'published_state'  => PublishedState::WORLD,
                'publish_at'       => Carbon::now()->subMinute(),
            ]);

            // Act
            $this->artisan(PublishScheduled::class)->assertSuccessful();

            // Assert
            $draft->refresh();
            $this->assertEquals(PublishedState::ALL[PublishedState::UNPUBLISHED], $draft->published_state_id);
            $this->assertTrue($draft->published_at->isBefore(Carbon::now()->subMonth()), 'An upgrade draft must never be stamped as published');
            $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
                'dungeon_route_id' => $draft->id,
            ]);
        } finally {
            if ($draft !== null) {
                DungeonRouteScheduledPublish::where('dungeon_route_id', $draft->id)->delete();
                DungeonRoute::find($draft->id)?->delete();
            }
            $original->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTeamRouteOnActiveDungeon(bool $withRequiredEnemies, array $attributes = []): DungeonRoute
    {
        [$activeDungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            dungeonActive: true,
            resolve: static fn(Dungeon $dungeon, MappingVersion $mappingVersion): ?bool => $mappingVersion->enemies()->where('required', true)->exists() === $withRequiredEnemies ? true : null,
        );

        return DungeonRoute::factory()->create(array_merge([
            'dungeon_id'         => $activeDungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
            'expires_at'         => null,
        ], $attributes));
    }
}
