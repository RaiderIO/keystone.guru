<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

            // Assert - a job for this route must exist (so the negative assertion below isn't vacuous),
            // but it must not be a forced one
            $response->assertOk();
            Queue::assertPushed(
                ProcessRouteFloorThumbnail::class,
                $this->isJobFor($dungeonRoute),
            );
            Queue::assertNotPushed(
                ProcessRouteFloorThumbnail::class,
                $this->isForcedJobFor($dungeonRoute),
            );
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function thumbnailsregeneratesubmit_givenRouteWithHeroThumbnail_alsoQueuesTheHeroVariant(): void
    {
        // Arrange - refreshing a route means refreshing the variants it actually has, so a route carrying a
        // hero thumbnail must get its (blank) hero render replaced too, not just the standard one.
        Queue::fake();

        $dungeonRoute = $this->createRouteWithNewerThumbnailTimestamp();
        $thumbnail    = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $dungeonRoute->dungeon->floors()->active()->firstOrFail()->id,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);

        try {
            // Act
            $this->be($this->getAdmin());
            $response = $this->post(route('admin.tools.thumbnails.regenerate.submit'), [
                'dungeon_id'   => $dungeonRoute->dungeon_id,
                'only_missing' => 0,
                'force'        => 1,
            ]);

            // Assert - both variants queued for this route
            $response->assertOk();
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJobFor($dungeonRoute, DungeonRouteThumbnailVariant::Standard));
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJobFor($dungeonRoute, DungeonRouteThumbnailVariant::Hero));
        } finally {
            $thumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function thumbnailsregeneratesubmit_givenRouteWithoutHeroThumbnail_doesNotQueueTheHeroVariant(): void
    {
        // Arrange - a route that never had a hero thumbnail must not gain one here: only the discovery hero
        // band displays it, and a 1600x640 render per route across a dungeon is far more expensive than the
        // standard one. thumbnail:ensureheroes owns creating hero thumbnails for the routes that need them.
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
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJobFor($dungeonRoute, DungeonRouteThumbnailVariant::Standard));
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJobFor($dungeonRoute, DungeonRouteThumbnailVariant::Hero));
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * The controller sweeps every route in the posted dungeon, and queueThumbnailRefresh() writes
     * thumbnail_refresh_queued_at on each one - so this must pick a dungeon that has no seeded routes
     * of its own. Otherwise the test would permanently mutate seeded rows it cannot restore, and the
     * assertions could be satisfied by another route's jobs rather than the one under test.
     */
    private function createRouteWithNewerThumbnailTimestamp(): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            constraint: static fn(Builder $query) => $query->whereDoesntHave('dungeonRoutes'),
        );

        return DungeonRoute::factory()->create([
            'dungeon_id'           => $dungeon->id,
            'mapping_version_id'   => $mappingVersion->id,
            'thumbnail_updated_at' => Carbon::now()->addDay(),
        ]);
    }

    private function getAdmin(): User
    {
        $admin = User::query()->whereHas('roles', static fn($query) => $query->where('name', Role::ROLE_ADMIN))->firstOrFail();

        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'Expected an admin user to exist (seed the DB).');

        return $admin;
    }

    private function isJobFor(DungeonRoute $dungeonRoute): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): DungeonRoute => $this->dungeonRoute)->call($job)->id === $dungeonRoute->id;
    }

    private function isForcedJobFor(DungeonRoute $dungeonRoute): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => $this->isJobFor($dungeonRoute)($job)
            && (fn(): bool => $this->force)->call($job);
    }

    private function isVariantJobFor(DungeonRoute $dungeonRoute, DungeonRouteThumbnailVariant $variant): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => $this->isJobFor($dungeonRoute)($job)
            && (fn(): DungeonRouteThumbnailVariant => $this->variant)->call($job) === $variant;
    }
}
