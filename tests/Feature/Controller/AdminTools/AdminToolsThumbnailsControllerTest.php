<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsThumbnailsControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function thumbnailsregeneratesubmit_givenForce_dispatchesForcedJobs(): void
    {
        // Arrange - a route whose thumbnail is newer than the route itself, which is what a blank render
        // leaves behind. Without force the queued job short-circuits on that timestamp, so the admin tool
        // could never replace such a thumbnail (#4101).
        Queue::fake();

        $dungeonRoute = $this->createRouteWithNewerThumbnailTimestamp();

        try {
            // Act
            $this->be($this->getAdmin());
            $response = $this->post(route('admin.tools.thumbnails.regenerate.submit'), [
                'dungeon_id'   => $dungeonRoute->dungeon_id,
                'only_missing' => 0,
                'force'        => 1,
            ]);

            // Assert
            $response->assertOk();
            Queue::assertPushed(
                ProcessRouteFloorThumbnail::class,
                $this->isForcedJobFor($dungeonRoute),
            );
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function thumbnailsregeneratesubmit_givenNoForce_dispatchesUnforcedJobs(): void
    {
        // Arrange - the checkbox is opt-in: leaving it off must keep the previous behaviour, so a routine
        // mass regeneration doesn't re-render every thumbnail on the site.
        Queue::fake();

        $dungeonRoute = $this->createRouteWithNewerThumbnailTimestamp();

        try {
            // Act
            $this->be($this->getAdmin());
            $response = $this->post(route('admin.tools.thumbnails.regenerate.submit'), [
                'dungeon_id'   => $dungeonRoute->dungeon_id,
                'only_missing' => 0,
            ]);

            // Assert
            $response->assertOk();
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
            Queue::assertNotPushed(
                ProcessRouteFloorThumbnail::class,
                $this->isForcedJobFor($dungeonRoute),
            );
        } finally {
            $dungeonRoute->delete();
        }
    }

    private function createRouteWithNewerThumbnailTimestamp(): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'           => $dungeon->id,
            'mapping_version_id'   => $mappingVersion->id,
            'thumbnail_updated_at' => Carbon::now()->addDay(),
        ]);

        return $dungeonRoute;
    }

    private function getAdmin(): User
    {
        $admin = User::query()->whereHas('roles', static fn($query) => $query->where('name', Role::ROLE_ADMIN))->firstOrFail();

        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'Expected an admin user to exist (seed the DB).');

        return $admin;
    }

    private function isForcedJobFor(DungeonRoute $dungeonRoute): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): bool => $this->force)->call($job)
            && (fn(): DungeonRoute => $this->dungeonRoute)->call($job)->id === $dungeonRoute->id;
    }
}
