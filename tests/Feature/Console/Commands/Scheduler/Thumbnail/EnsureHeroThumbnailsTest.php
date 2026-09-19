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
use Illuminate\Support\Carbon;
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
    public function handle_givenHeroRoutes_queuesHeroAndFrontPageVariantJobs(): void
    {
        // Arrange - explicitly mock the season/hero-route resolution rather than relying on ambient
        // seed data (a real environment can have zero current-season popular/weekly routes, which
        // would make this test vacuously pass or, worse, silently assert nothing). The front page's
        // "popular this week" section shows the top route per dungeon, already a subset of these hero
        // routes, so the front-page variant is queued for the same set - see EnsureHeroThumbnails.
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

            // Assert - the mocked hero route was queued for both variants, and nothing else
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJob(DungeonRouteThumbnailVariant::Hero));
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJob(DungeonRouteThumbnailVariant::FrontPage));
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, fn(ProcessRouteFloorThumbnail $job): bool => !$this->isVariantJob(DungeonRouteThumbnailVariant::Hero)($job) && !$this->isVariantJob(DungeonRouteThumbnailVariant::FrontPage)($job));
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenFreshHeroAndFrontPageThumbnailsAndNoForce_queuesNothing(): void
    {
        // Arrange - a route that already has a fresh thumbnail of both variants for every floor it
        // renders. This is the state a blank render leaves behind (the row exists and is newer than
        // the route), which is why the scheduled run would otherwise never replace it.
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([
            DungeonRouteThumbnailVariant::Hero,
            DungeonRouteThumbnailVariant::FrontPage,
        ]);
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
    public function handle_givenFreshHeroThumbnailOnlyAndNoForce_queuesOnlyFrontPageJobs(): void
    {
        // Arrange - freshness is gated per variant (hasFreshThumbnailForVariant), so a route with only
        // a fresh hero thumbnail must still get a front-page render queued.
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([DungeonRouteThumbnailVariant::Hero]);
        $this->mockHeroRouteResolution($dungeonRoute);

        try {
            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJob(DungeonRouteThumbnailVariant::Hero));
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJob(DungeonRouteThumbnailVariant::FrontPage));
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenFreshHeroAndFrontPageThumbnailAndForce_queuesBothVariantJobsAnyway(): void
    {
        // Arrange - same fresh-thumbnail state as above; --force is the recovery lever that must get
        // past the freshness gate so blank hero/front-page renders can be replaced (#4101).
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([
            DungeonRouteThumbnailVariant::Hero,
            DungeonRouteThumbnailVariant::FrontPage,
        ]);
        $this->mockHeroRouteResolution($dungeonRoute);

        try {
            // Act
            $this->artisan(EnsureHeroThumbnails::class, ['--force' => true])->assertSuccessful();

            // Assert
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJob(DungeonRouteThumbnailVariant::Hero));
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isVariantJob(DungeonRouteThumbnailVariant::FrontPage));
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenHeroRoutes_stampsLastHeroAt(): void
    {
        // Arrange
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([DungeonRouteThumbnailVariant::Hero]);
        $this->mockHeroRouteResolution($dungeonRoute);

        try {
            $updatedAt = $dungeonRoute->updated_at->toDateTimeString();

            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            $dungeonRoute->refresh();
            $this->assertTrue($dungeonRoute->last_hero_at->isToday());
            $this->assertSame($updatedAt, $dungeonRoute->updated_at->toDateTimeString());
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenRouteThatLeftTheHeroSetLongAgo_deletesHeroAndFrontPageThumbnailsOnly(): void
    {
        // Arrange
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([
            DungeonRouteThumbnailVariant::Hero,
            DungeonRouteThumbnailVariant::FrontPage,
            DungeonRouteThumbnailVariant::Standard,
            DungeonRouteThumbnailVariant::Custom,
        ]);
        $this->mockHeroRouteResolution(null);

        try {
            $this->setLastHeroAt($dungeonRoute, now()->subDays(15));

            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertVariantsRemaining(
                $thumbnails,
                [DungeonRouteThumbnailVariant::Standard, DungeonRouteThumbnailVariant::Custom],
            );
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenRouteThatLeftTheHeroSetRecently_keepsItsThumbnails(): void
    {
        // Arrange
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([
            DungeonRouteThumbnailVariant::Hero,
            DungeonRouteThumbnailVariant::FrontPage,
        ]);
        $this->mockHeroRouteResolution(null);

        try {
            $this->setLastHeroAt($dungeonRoute, now()->subDays(5));

            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertVariantsRemaining(
                $thumbnails,
                [DungeonRouteThumbnailVariant::Hero, DungeonRouteThumbnailVariant::FrontPage],
            );
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenRouteStillInTheHeroSetWithOldStamp_keepsItsThumbnailsAndRefreshesTheStamp(): void
    {
        // Arrange
        Queue::fake();

        [$dungeonRoute, $thumbnails] = $this->createRouteWithFreshThumbnails([DungeonRouteThumbnailVariant::Hero]);
        $this->mockHeroRouteResolution($dungeonRoute);

        try {
            $this->setLastHeroAt($dungeonRoute, now()->subDays(60));

            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertVariantsRemaining($thumbnails, [DungeonRouteThumbnailVariant::Hero]);
            $this->assertTrue($dungeonRoute->refresh()->last_hero_at->isToday());
        } finally {
            $this->deleteThumbnails($thumbnails);
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenUnstampedRouteOutsideTheHeroSet_judgesItByTheThumbnailAge(): void
    {
        // Arrange
        Queue::fake();

        [$oldRoute, $oldThumbnails]       = $this->createRouteWithFreshThumbnails([DungeonRouteThumbnailVariant::Hero]);
        [$recentRoute, $recentThumbnails] = $this->createRouteWithFreshThumbnails([DungeonRouteThumbnailVariant::Hero]);
        $this->mockHeroRouteResolution(null);

        try {
            DungeonRouteThumbnail::query()->whereIn('id', $oldThumbnails->pluck('id'))
                ->update(['updated_at' => now()->subDays(20)->toDateTimeString()]);
            DungeonRouteThumbnail::query()->whereIn('id', $recentThumbnails->pluck('id'))
                ->update(['updated_at' => now()->subDays(2)->toDateTimeString()]);

            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertVariantsRemaining($oldThumbnails, []);
            $this->assertVariantsRemaining($recentThumbnails, [DungeonRouteThumbnailVariant::Hero]);
        } finally {
            $this->deleteThumbnails($oldThumbnails);
            $this->deleteThumbnails($recentThumbnails);
            $oldRoute->delete();
            $recentRoute->delete();
        }
    }

    /**
     * A route whose thumbnails exist for every floor the refresh would render, for each of the given
     * variants, each stamped newer than the route itself - the exact condition
     * hasFreshThumbnailForVariant() gates on.
     *
     * @param  array<int, DungeonRouteThumbnailVariant>                          $variants
     * @return array{0: DungeonRoute, 1: Collection<int, DungeonRouteThumbnail>}
     */
    private function createRouteWithFreshThumbnails(array $variants): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $activeFloors = $dungeon->floorsForMapFacade($mappingVersion, true)->active()->get();

        $thumbnails = collect();
        foreach ($variants as $variant) {
            $activeFloors->each(function (Floor $floor) use ($dungeonRoute, $variant, $thumbnails): void {
                $thumbnail = DungeonRouteThumbnail::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'floor_id'         => $floor->id,
                    'variant'          => $variant,
                ]);

                // updated_at is set by the model on create; force it past the route's own timestamp
                DungeonRouteThumbnail::where('id', $thumbnail->id)
                    ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

                $thumbnails->push($thumbnail);
            });
        }

        return [$dungeonRoute, $thumbnails];
    }

    /**
     * @throws Exception
     */
    private function mockHeroRouteResolution(?DungeonRoute $dungeonRoute): void
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(Season::query()->firstOrFail());
        app()->instance(SeasonServiceInterface::class, $seasonService);

        $discoverService = $this->createMockPublic(DiscoverServiceInterface::class);
        $discoverService->method('heroRoutes')->willReturn($dungeonRoute === null ? collect() : collect([$dungeonRoute]));
        app()->instance(DiscoverServiceInterface::class, $discoverService);
    }

    private function setLastHeroAt(DungeonRoute $dungeonRoute, Carbon $lastHeroAt): void
    {
        // Through the query builder so Eloquent does not overwrite updated_at
        DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update(['last_hero_at' => $lastHeroAt->toDateTimeString()]);
    }

    /**
     * @param Collection<int, DungeonRouteThumbnail>   $thumbnails
     * @param array<int, DungeonRouteThumbnailVariant> $expectedVariants The variants that must still exist
     */
    private function assertVariantsRemaining(Collection $thumbnails, array $expectedVariants): void
    {
        foreach ($thumbnails as $thumbnail) {
            if (in_array($thumbnail->variant, $expectedVariants, true)) {
                $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnail->id]);
            } else {
                $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $thumbnail->id]);
            }
        }
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

    private function isVariantJob(DungeonRouteThumbnailVariant $variant): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): DungeonRouteThumbnailVariant => $this->variant)->call($job) === $variant;
    }
}
