<?php

namespace Tests\Feature\Console\Commands\Scheduler\DungeonRoute;

use App\Console\Commands\Scheduler\DungeonRoute\PublishScheduled;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteScheduledPublish;
use App\Models\PublishedState;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
final class PublishScheduledTest extends PublicTestCase
{
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
        // Arrange
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'published_state'  => PublishedState::TEAM,
            'publish_at'       => Carbon::now()->subMinute(),
        ]);

        // Act
        $this->artisan(PublishScheduled::class)->assertSuccessful();

        // Assert
        $this->dungeonRoute->refresh();
        $this->assertEquals(PublishedState::ALL[PublishedState::TEAM], $this->dungeonRoute->published_state_id);
        $this->assertDatabaseMissing('dungeon_route_scheduled_publishes', [
            'dungeon_route_id' => $this->dungeonRoute->id,
        ]);
    }

    #[Test]
    public function handle_givenDueScheduleForWorldState_setsPublishedAt(): void
    {
        // Arrange — use an active dungeon route
        $activeDungeon = \App\Models\Dungeon::query()
            ->where('active', true)
            ->whereNotNull('challenge_mode_id')
            ->first();

        if ($activeDungeon === null) {
            $this->markTestSkipped('No active dungeon available.');
        }

        $mappingVersion = $activeDungeon->getCurrentMappingVersion();
        if ($mappingVersion === null) {
            $this->markTestSkipped('No mapping version available for active dungeon.');
        }

        /** @var DungeonRoute $worldRoute */
        $worldRoute = DungeonRoute::factory()->make([
            'dungeon_id'         => $activeDungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
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
            $this->assertInstanceOf(Carbon::class, $worldRoute->published_at);
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
        // Arrange — use an inactive dungeon
        $inactiveDungeon = \App\Models\Dungeon::query()
            ->where('active', false)
            ->whereNotNull('challenge_mode_id')
            ->first();

        if ($inactiveDungeon === null) {
            $this->markTestSkipped('No inactive dungeon available.');
        }

        $mappingVersion = $inactiveDungeon->getCurrentMappingVersion();
        if ($mappingVersion === null) {
            $this->markTestSkipped('No mapping version available for inactive dungeon.');
        }

        /** @var DungeonRoute $inactiveRoute */
        $inactiveRoute = DungeonRoute::factory()->make([
            'dungeon_id'         => $inactiveDungeon->id,
            'mapping_version_id' => $mappingVersion->id,
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
}
