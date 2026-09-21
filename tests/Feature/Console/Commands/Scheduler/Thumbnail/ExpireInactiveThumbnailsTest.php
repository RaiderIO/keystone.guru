<?php

namespace Tests\Feature\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\Thumbnail\ExpireInactiveThumbnails;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Thumbnail')]
final class ExpireInactiveThumbnailsTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string NEVER_RENDERED_UPDATED_AT = '1970-01-01 12:00:00';

    private const string NEVER_QUEUED_AT = '1970-01-01 00:00:00';

    #[Test]
    public function handle_givenRouteInactiveLongerThanThreshold_deletesStandardAndFrontPageThumbnailsAndFiles(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [
                    DungeonRouteThumbnailVariant::Standard,
                    DungeonRouteThumbnailVariant::FrontPage,
                ],
                inactiveDays: 120,
            );
            $updatedAt = $dungeonRoute->updated_at->toDateTimeString();

            // Act
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();

            // Assert
            foreach ($thumbnails as $thumbnail) {
                $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $thumbnail->id]);
                $this->assertDatabaseMissing('files', ['id' => $thumbnail->file_id]);
                Storage::disk(config('filesystems.default'))->assertMissing($thumbnail->file->path);
            }

            $dungeonRoute->refresh();
            $this->assertSame(self::NEVER_RENDERED_UPDATED_AT, $dungeonRoute->thumbnail_updated_at->toDateTimeString());
            $this->assertSame(self::NEVER_QUEUED_AT, $dungeonRoute->thumbnail_refresh_queued_at->toDateTimeString());
            $this->assertSame($updatedAt, $dungeonRoute->updated_at->toDateTimeString());
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenRouteInactiveLongerThanThreshold_keepsCustomAndHeroThumbnails(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [
                    DungeonRouteThumbnailVariant::Standard,
                    DungeonRouteThumbnailVariant::Custom,
                    DungeonRouteThumbnailVariant::Hero,
                ],
                inactiveDays: 120,
            );

            // Act
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();

            // Assert
            foreach ($thumbnails as $thumbnail) {
                $expired = $thumbnail->variant === DungeonRouteThumbnailVariant::Standard;

                if ($expired) {
                    $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $thumbnail->id]);
                } else {
                    $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnail->id]);
                    Storage::disk(config('filesystems.default'))->assertExists($thumbnail->file->path);
                }
            }
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenRecentlyEditedRoute_keepsThumbnails(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 10,
            );

            // Act
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnails->first()->id]);
            $this->assertNotSame(
                self::NEVER_RENDERED_UPDATED_AT,
                $dungeonRoute->refresh()->thumbnail_updated_at->toDateTimeString(),
            );
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenRouteEditedLongAgoButRecentlyAccessed_keepsThumbnails(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 200,
                lastAccessedDaysAgo: 10,
            );

            // Act
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnails->first()->id]);
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenRouteEditedRecentlyButAccessedLongAgo_keepsThumbnails(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 5,
                lastAccessedDaysAgo: 300,
            );

            // Act
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnails->first()->id]);
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenDryRun_deletesNothingAndReportsTheCount(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 120,
            );

            // Act
            $this->artisan(ExpireInactiveThumbnails::class, ['--dry-run' => true])
                ->expectsOutputToContain('Would expire the thumbnails of')
                ->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnails->first()->id]);
            Storage::disk(config('filesystems.default'))->assertExists($thumbnails->first()->file->path);
            $this->assertNotSame(
                self::NEVER_RENDERED_UPDATED_AT,
                $dungeonRoute->refresh()->thumbnail_updated_at->toDateTimeString(),
            );
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenLimitOfZero_deletesNothing(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 120,
            );

            // Act
            $this->artisan(ExpireInactiveThumbnails::class, ['--limit' => 0])
                ->expectsOutputToContain('Expired the thumbnails of 0 inactive routes')
                ->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnails->first()->id]);
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function dungeonRoutesDisplayed_givenExpiredRoute_queuesRenderAndStampsAccess(): void
    {
        // Arrange
        Queue::fake();
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 120,
            );
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();

            // Act
            $result = app()->make(ThumbnailServiceInterface::class)->dungeonRoutesDisplayed(collect([$dungeonRoute->refresh()]));

            // Assert
            $this->assertTrue($result);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
            $this->assertTrue($dungeonRoute->refresh()->last_accessed_at->isToday());
        } finally {
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    #[Test]
    public function handle_givenDeletionFailsHalfway_stillResetsTimestampsSoDisplayQueuesRender(): void
    {
        // Arrange
        Queue::fake();
        $dungeonRoute = null;
        $thumbnails   = collect();

        try {
            [$dungeonRoute, $thumbnails] = $this->createRouteWithThumbnails(
                [DungeonRouteThumbnailVariant::Standard, DungeonRouteThumbnailVariant::Standard],
                inactiveDays: 120,
            );
            // The second thumbnail's stored object cannot be deleted, as when a storage call fails
            File::query()->whereKey($thumbnails->last()->file_id)->update(['disk' => 'disk-that-does-not-exist']);

            // Act
            $this->artisan(ExpireInactiveThumbnails::class)->assertSuccessful();
            $result = app()->make(ThumbnailServiceInterface::class)->dungeonRoutesDisplayed(collect([$dungeonRoute->refresh()]));

            // Assert
            $this->assertSame(self::NEVER_RENDERED_UPDATED_AT, $dungeonRoute->thumbnail_updated_at->toDateTimeString());
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $thumbnails->last()->id]);
            $this->assertTrue($result);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            // The row still points at the broken disk, which would make its cleanup fail as well
            File::query()->whereIn('id', $thumbnails->pluck('file_id'))->update(['disk' => config('filesystems.default')]);
            $this->deleteRoute($dungeonRoute, $thumbnails);
        }
    }

    /**
     * A non-sandbox route with one file-backed thumbnail per requested variant, last edited $inactiveDays ago.
     *
     * @param  array<int, DungeonRouteThumbnailVariant>                          $variants
     * @return array{0: DungeonRoute, 1: Collection<int, DungeonRouteThumbnail>}
     */
    private function createRouteWithThumbnails(array $variants, int $inactiveDays, ?int $lastAccessedDaysAgo = null): array
    {
        Storage::fake(config('filesystems.default'));

        [$dungeon, $mappingVersion] = $this->findDungeon();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => 1,
            'expires_at'         => null,
        ]);

        // Written through the query builder so Eloquent does not overwrite updated_at
        DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update([
            'updated_at'                  => now()->subDays($inactiveDays)->toDateTimeString(),
            'thumbnail_updated_at'        => now()->subDays($inactiveDays)->addHour()->toDateTimeString(),
            'thumbnail_refresh_queued_at' => now()->subDays($inactiveDays)->toDateTimeString(),
            'last_accessed_at'            => $lastAccessedDaysAgo === null ? null : now()->subDays($lastAccessedDaysAgo)->toDateTimeString(),
        ]);

        $floor      = $dungeon->floorsForMapFacade($mappingVersion, true)->active()->firstOrFail();
        $thumbnails = collect();
        foreach ($variants as $variant) {
            $thumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'variant'          => $variant,
            ]);

            $path = sprintf('thumbnails/expire_test_%d.jpg', $thumbnail->id);
            Storage::disk(config('filesystems.default'))->put($path, 'fake-image-bytes');

            $file = File::create([
                'model_id'    => $thumbnail->id,
                'model_class' => DungeonRouteThumbnail::class,
                'disk'        => config('filesystems.default'),
                'path'        => $path,
            ]);
            $thumbnail->update(['file_id' => $file->id]);

            $thumbnails->push($thumbnail->refresh()->load('file'));
        }

        return [$dungeonRoute->refresh(), $thumbnails];
    }

    /**
     * @param Collection<int, DungeonRouteThumbnail> $thumbnails
     */
    private function deleteRoute(?DungeonRoute $dungeonRoute, Collection $thumbnails): void
    {
        foreach ($thumbnails as $thumbnail) {
            DungeonRouteThumbnail::query()->whereKey($thumbnail->id)->get()->each->delete();
        }

        $dungeonRoute?->delete();
    }
}
