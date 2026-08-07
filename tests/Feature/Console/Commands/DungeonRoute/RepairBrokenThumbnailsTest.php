<?php

namespace Tests\Feature\Console\Commands\DungeonRoute;

use App\Console\Commands\DungeonRoute\RepairBrokenThumbnails;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use Closure;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
final class RepairBrokenThumbnailsTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Every configured disk is faked, not just the default: thumbnails carry their own
        // File.disk (commonly 's3_user_uploads'), and an unfaked one would turn a disk-existence
        // check into a real S3 round trip. Combined with scopedToRoute(), which keeps the command
        // off every row this test did not create, that leaves no real storage call at all.
        foreach (array_keys(config('filesystems.disks')) as $disk) {
            Storage::fake($disk);
        }
    }

    private function createDungeonRoute(): DungeonRoute
    {
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }

    private function createThumbnail(DungeonRoute $dungeonRoute, bool $withFile, bool $missingFileRow = false, bool $custom = false): DungeonRouteThumbnail
    {
        $floor = $dungeonRoute->dungeon->floors()->where('facade', false)->first();

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'variant'          => $custom ? DungeonRouteThumbnailVariant::Custom : DungeonRouteThumbnailVariant::Standard,
        ]);

        if ($withFile) {
            $file = File::create([
                'model_id'    => $thumbnail->id,
                'model_class' => DungeonRouteThumbnail::class,
                'disk'        => config('filesystems.default'),
                'path'        => sprintf('thumbnails/%d.jpg', $thumbnail->id),
            ]);
            $thumbnail->update(['file_id' => $file->id]);

            if ($missingFileRow) {
                // Leave the dangling file_id but remove the File row it points at.
                File::query()->whereKey($file->id)->delete();
            }
        }

        return $thumbnail;
    }

    /**
     * Confines the command to the route under test.
     *
     * Without this it scans every thumbnail row in the shared dev database - 226k of them locally -
     * and, because setUp() fakes every disk, judges all of them broken: it then re-queues every
     * route in the database and deletes real fileless rows. That cost ~25 minutes per test and
     * mutated data the test never created. CI only got away with it because its database is
     * freshly seeded and nearly empty.
     *
     * @return array<string, array<int, int>>
     */
    private function scopedToRoute(DungeonRoute $dungeonRoute): array
    {
        return ['--dungeon-route-id' => [$dungeonRoute->id]];
    }

    /**
     * Matches a queued thumbnail job against the given route. The job keeps its route in a
     * protected property, so bind into the job instance to read it.
     *
     * @return Closure(ProcessRouteFloorThumbnail): bool
     */
    private function isThumbnailJobForRoute(DungeonRoute $dungeonRoute): Closure
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): int => $this->dungeonRoute->id)->call($job) === $dungeonRoute->id;
    }

    #[Test]
    public function handle_givenFilelessAndFileBackedThumbnails_deletesOnlyTheFilelessOnes(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute         = $this->createDungeonRoute();
        $nullFileThumbnail    = $this->createThumbnail($dungeonRoute, false);
        $missingFileThumbnail = $this->createThumbnail($dungeonRoute, true, true);
        $validThumbnail       = $this->createThumbnail($dungeonRoute, true);
        // The valid thumbnail's disk object must exist so it is not treated as disk-missing.
        Storage::disk(config('filesystems.default'))->put($validThumbnail->file->path, 'fake-image-bytes');

        try {
            // Act
            $this->artisan(RepairBrokenThumbnails::class, $this->scopedToRoute($dungeonRoute))->assertSuccessful();

            // Assert
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $nullFileThumbnail->id]);
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $missingFileThumbnail->id]);
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $validThumbnail->id]);
            // The route's only file-backed thumbnail has its disk object, so it must not be re-queued.
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, $this->isThumbnailJobForRoute($dungeonRoute));
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenThumbnailFileMissingFromDisk_requeuesTheRouteForRegeneration(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRoute();
        // File row exists, but no object is put on the (faked) disk, so it is "missing from disk".
        $this->createThumbnail($dungeonRoute, true);

        try {
            // Act
            $this->artisan(RepairBrokenThumbnails::class, $this->scopedToRoute($dungeonRoute))->assertSuccessful();

            // Assert
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isThumbnailJobForRoute($dungeonRoute));
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenCustomThumbnailFileMissingFromDisk_reportsItWithoutRequeueingOrDeleting(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRoute();
        // Custom (API-requested) thumbnail whose File row exists but whose disk object is missing:
        // re-queueing only regenerates default per-floor thumbnails, so it must be reported instead.
        $customThumbnail = $this->createThumbnail($dungeonRoute, true, false, true);

        try {
            // Act
            $this->artisan(RepairBrokenThumbnails::class, $this->scopedToRoute($dungeonRoute))
                ->expectsOutputToContain('cannot be repaired by re-queueing')
                ->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $customThumbnail->id]);
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, $this->isThumbnailJobForRoute($dungeonRoute));
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenDryRunOption_deletesNothingAndQueuesNothing(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute         = $this->createDungeonRoute();
        $nullFileThumbnail    = $this->createThumbnail($dungeonRoute, false);
        $diskMissingThumbnail = $this->createThumbnail($dungeonRoute, true);

        try {
            // Act
            $this->artisan(RepairBrokenThumbnails::class, [...$this->scopedToRoute($dungeonRoute), '--dry-run' => true])->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $nullFileThumbnail->id]);
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $diskMissingThumbnail->id]);
            Queue::assertNothingPushed();
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    /**
     * The scoping option is what keeps this suite from running the repair across the entire
     * database, so prove it actually excludes routes it was not given rather than trusting that the
     * other tests happen to pass.
     */
    #[Test]
    public function handle_givenDungeonRouteIdOption_leavesThumbnailsOfOtherRoutesUntouched(): void
    {
        // Arrange
        Queue::fake();

        $repairedRoute     = $this->createDungeonRoute();
        $untouchedRoute    = $this->createDungeonRoute();
        $repairedFileless  = $this->createThumbnail($repairedRoute, false);
        $untouchedFileless = $this->createThumbnail($untouchedRoute, false);

        try {
            // Act
            $this->artisan(RepairBrokenThumbnails::class, $this->scopedToRoute($repairedRoute))->assertSuccessful();

            // Assert
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $repairedFileless->id]);
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $untouchedFileless->id]);
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, $this->isThumbnailJobForRoute($untouchedRoute));
        } finally {
            foreach ([$repairedRoute, $untouchedRoute] as $route) {
                DungeonRouteThumbnail::where('dungeon_route_id', $route->id)->get()->each->delete();
                $route->delete();
            }
        }
    }
}
