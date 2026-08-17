<?php

namespace Tests\Feature\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\Thumbnail\EnsureHeroThumbnails;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\Floor\Floor;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Thumbnail')]
final class EnsureHeroThumbnailsTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenHeroRoutes_queuesOnlyHeroVariantJobs(): void
    {
        // Arrange - explicitly mock the season/hero-route resolution rather than relying on ambient
        // seed data (a real environment can have zero current-season popular/weekly routes, which
        // would make this test vacuously pass or, worse, silently assert nothing)
        Queue::fake();

        $season       = Season::query()->firstOrFail();
        $dungeonRoute = DungeonRoute::factory()->create();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($season);
        app()->instance(SeasonServiceInterface::class, $seasonService);

        $discoverService = $this->createMockPublic(DiscoverServiceInterface::class);
        $discoverService->method('heroRoutes')->willReturn(collect([$dungeonRoute]));
        app()->instance(DiscoverServiceInterface::class, $discoverService);

        try {
            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert - the mocked hero route was queued, and every queued job is Hero-variant (never Standard)
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isHeroVariantJob());
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, fn(ProcessRouteFloorThumbnail $job): bool => !$this->isHeroVariantJob()($job));
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenFreshHeroThumbnailAndNoForce_queuesNothing(): void
    {
        // Arrange - a route that already has a fresh hero thumbnail for every floor it renders. This is
        // the state a blank render leaves behind (the row exists and is newer than the route), which is
        // why the scheduled run would otherwise never replace it.
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshHeroThumbnails();
        $this->mockHeroRouteResolution($dungeonRoute);

        try {
            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenFreshHeroThumbnailAndForce_queuesHeroJobsAnyway(): void
    {
        // Arrange - same fresh-thumbnail state as above; --force is the recovery lever that must get
        // past the freshness gate so blank hero renders can be replaced (#4101).
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshHeroThumbnails();
        $this->mockHeroRouteResolution($dungeonRoute);

        try {
            // Act
            $this->artisan(EnsureHeroThumbnails::class, ['--force' => true])->assertSuccessful();

            // Assert
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isHeroVariantJob());
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * A route whose hero thumbnails exist for every floor the refresh would render, each stamped newer
     * than the route itself - the exact condition hasFreshThumbnailForVariant() gates on.
     *
     * @return array{0: DungeonRoute, 1: Collection<int, DungeonRouteThumbnail>}
     */
    private function createRouteWithFreshHeroThumbnails(): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnails = $dungeon->floorsForMapFacade($mappingVersion, true)->active()->get()
            ->map(function (Floor $floor) use ($dungeonRoute): DungeonRouteThumbnail {
                $thumbnail = DungeonRouteThumbnail::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'floor_id'         => $floor->id,
                    'variant'          => DungeonRouteThumbnailVariant::Hero,
                ]);

                // updated_at is set by the model on create; force it past the route's own timestamp
                DungeonRouteThumbnail::where('id', $thumbnail->id)
                    ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

                return $thumbnail;
            });

        return [$dungeonRoute, $thumbnails];
    }

    /**
     * @throws Exception
     */
    private function mockHeroRouteResolution(DungeonRoute $dungeonRoute): void
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(Season::query()->firstOrFail());
        app()->instance(SeasonServiceInterface::class, $seasonService);

        $discoverService = $this->createMockPublic(DiscoverServiceInterface::class);
        $discoverService->method('heroRoutes')->willReturn(collect([$dungeonRoute]));
        app()->instance(DiscoverServiceInterface::class, $discoverService);
    }

    /**
     * @param Collection<int, DungeonRouteThumbnail> $thumbnails
     */
    private function deleteThumbnails(Collection $thumbnails): void
    {
        foreach ($thumbnails as $thumbnail) {
            $thumbnail->delete();
        }
    }

    private function isHeroVariantJob(): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): DungeonRouteThumbnailVariant => $this->variant)->call($job) === DungeonRouteThumbnailVariant::Hero;
    }
}
