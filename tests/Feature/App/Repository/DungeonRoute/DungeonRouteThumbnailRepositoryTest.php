<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use App\Repositories\Database\DungeonRoute\DungeonRouteThumbnailRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteThumbnailRepository')]
final class DungeonRouteThumbnailRepositoryTest extends PublicTestCase
{
    use ProvidesDungeon;

    private DungeonRouteThumbnailRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DungeonRouteThumbnailRepository();
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenNoThumbnailOfThatVariant_returnsFalse(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertFalse($result);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenNullMappingVersion_returnsFalse(): void
    {
        // Arrange - mapping_version_id is nullable in the schema (legacy data), so the check must not
        // blow up trying to compute an expected floor count from a null mapping version.
        $dungeonRoute = DungeonRoute::factory()->create(['mapping_version_id' => null]);

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertFalse($result);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenThumbnailRenderedAfterLastContentChange_returnsTrue(): void
    {
        // Arrange - uses a dungeon with exactly one non-facade floor so the new expected-floor-count
        // check (one thumbnail == one floor) doesn't flake on dungeons with several floors.
        $dungeon        = $this->getDungeonWithExactlyOneNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);
        DungeonRouteThumbnail::where('id', $thumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertTrue($result);
        } finally {
            $thumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenThumbnailRenderedBeforeLastContentChange_returnsFalse(): void
    {
        // Arrange
        $dungeon        = $this->getDungeonWithExactlyOneNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);
        DungeonRouteThumbnail::where('id', $thumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->subDay()]);

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertFalse($result);
        } finally {
            $thumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenFreshCustomVariantThumbnail_returnsTrue(): void
    {
        // Arrange - the legacy 'custom' boolean is intentionally left false here: 'variant' is the
        // single source of truth, and gating on both used to make the Custom variant unmatchable.
        $dungeon        = $this->getDungeonWithExactlyOneNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Custom,
        ]);
        DungeonRouteThumbnail::where('id', $thumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Custom);

            // Assert
            $this->assertTrue($result);
        } finally {
            $thumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenOneFreshAndOneStaleFloorThumbnail_returnsFalse(): void
    {
        // Arrange - a partially-failed multi-floor render: one floor's thumbnail is fresh, but another
        // floor still carries a stale row from before the route's last content change. The stale floor
        // must not be masked by the fresh one.
        //
        // Capped at two active floors on purpose: hasFreshThumbnailForVariant() first compares the
        // thumbnail row count against the dungeon's active floor count, so a dungeon with three or
        // more floors would return false on that short-circuit and the staleness check this test is
        // about would never run.
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, maxActiveFloors: 2);
        $floor                      = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute               = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $freshThumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);
        DungeonRouteThumbnail::where('id', $freshThumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

        $staleThumbnail = DungeonRouteThumbnail::create([
            // No second real floor is guaranteed to exist on every matching dungeon fixture, but this
            // method only reads dungeon_route_thumbnails (never joins floors), so a distinct synthetic
            // floor_id exercises the "second floor" scenario without needing one.
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id + 1,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);
        DungeonRouteThumbnail::where('id', $staleThumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->subDay()]);

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertFalse($result);
        } finally {
            $freshThumbnail->delete();
            $staleThumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenMissingThumbnailForOneOfMultipleFloors_returnsFalse(): void
    {
        // Arrange - a route whose dungeon has N non-facade floors, but hero thumbnails only exist (and
        // are fresh) for N-1 of them. This is the fully-missing-floor case: a floor with zero rows has
        // no updated_at to be caught as stale by the min() check, so it needs its own row-count check.
        $dungeon        = $this->getDungeonWithMultipleNonFacadeFloors();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floors         = $dungeon->floors()->where('facade', false)->where('active', true)->get();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnails = $floors->slice(0, -1)->map(function ($floor) use ($dungeonRoute) {
            $thumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'custom'           => false,
                'variant'          => DungeonRouteThumbnailVariant::Hero,
            ]);
            DungeonRouteThumbnail::where('id', $thumbnail->id)
                ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

            return $thumbnail;
        });

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertFalse($result);
        } finally {
            $thumbnails->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function hasFreshThumbnailForVariant_givenFreshThumbnailsForAllFloors_returnsTrue(): void
    {
        // Arrange - the complete-set counterpart to the missing-floor test above: every floor has a
        // fresh row, so the route must be reported as fresh.
        $dungeon        = $this->getDungeonWithMultipleNonFacadeFloors();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floors         = $dungeon->floors()->where('facade', false)->where('active', true)->get();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnails = $floors->map(function ($floor) use ($dungeonRoute) {
            $thumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'custom'           => false,
                'variant'          => DungeonRouteThumbnailVariant::Hero,
            ]);
            DungeonRouteThumbnail::where('id', $thumbnail->id)
                ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

            return $thumbnail;
        });

        try {
            // Act
            $result = $this->repository->hasFreshThumbnailForVariant($dungeonRoute, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertTrue($result);
        } finally {
            $thumbnails->each->delete();
            $dungeonRoute->delete();
        }
    }

    private function createDungeonRouteWithThumbnail(bool $withFile): DungeonRouteThumbnail
    {
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
        ]);

        if ($withFile) {
            $file = File::create([
                'model_id'    => $thumbnail->id,
                'model_class' => DungeonRouteThumbnail::class,
                'disk'        => config('filesystems.default'),
                'path'        => sprintf('thumbnails/%d.jpg', $thumbnail->id),
            ]);
            $thumbnail->update(['file_id' => $file->id]);
        }

        return $thumbnail;
    }

    #[Test]
    public function filelessThumbnailsQuery_givenDungeonRouteIds_onlyIncludesThumbnailsForThoseRoutes(): void
    {
        // Arrange
        $matchingThumbnail    = $this->createDungeonRouteWithThumbnail(false);
        $nonMatchingThumbnail = $this->createDungeonRouteWithThumbnail(false);

        try {
            // Act
            $result = $this->repository->filelessThumbnailsQuery([$matchingThumbnail->dungeon_route_id])->pluck('id');

            // Assert
            $this->assertTrue($result->contains($matchingThumbnail->id));
            $this->assertFalse($result->contains($nonMatchingThumbnail->id));
        } finally {
            $matchingThumbnail->delete();
            $nonMatchingThumbnail->delete();
            DungeonRoute::whereKey([$matchingThumbnail->dungeon_route_id, $nonMatchingThumbnail->dungeon_route_id])->delete();
        }
    }

    #[Test]
    public function filelessThumbnailsQuery_givenNoDungeonRouteIds_includesThumbnailsFromEveryRoute(): void
    {
        // Arrange
        $filelessThumbnail   = $this->createDungeonRouteWithThumbnail(false);
        $fileBackedThumbnail = $this->createDungeonRouteWithThumbnail(true);

        try {
            // Act
            $result = $this->repository->filelessThumbnailsQuery()->pluck('id');

            // Assert - unscoped, so it must find the fileless thumbnail regardless of which route
            // it belongs to, but never one that has a File.
            $this->assertTrue($result->contains($filelessThumbnail->id));
            $this->assertFalse($result->contains($fileBackedThumbnail->id));
        } finally {
            $filelessThumbnail->delete();
            File::whereKey($fileBackedThumbnail->file_id)->delete();
            $fileBackedThumbnail->delete();
            DungeonRoute::whereKey([$filelessThumbnail->dungeon_route_id, $fileBackedThumbnail->dungeon_route_id])->delete();
        }
    }

    #[Test]
    public function fileBackedThumbnailsQuery_givenDungeonRouteIds_onlyIncludesThumbnailsForThoseRoutes(): void
    {
        // Arrange
        $matchingThumbnail    = $this->createDungeonRouteWithThumbnail(true);
        $nonMatchingThumbnail = $this->createDungeonRouteWithThumbnail(true);

        try {
            // Act
            $result = $this->repository->fileBackedThumbnailsQuery([$matchingThumbnail->dungeon_route_id])->pluck('id');

            // Assert
            $this->assertTrue($result->contains($matchingThumbnail->id));
            $this->assertFalse($result->contains($nonMatchingThumbnail->id));
        } finally {
            File::whereKey($matchingThumbnail->file_id)->delete();
            File::whereKey($nonMatchingThumbnail->file_id)->delete();
            $matchingThumbnail->delete();
            $nonMatchingThumbnail->delete();
            DungeonRoute::whereKey([$matchingThumbnail->dungeon_route_id, $nonMatchingThumbnail->dungeon_route_id])->delete();
        }
    }

    #[Test]
    public function fileBackedThumbnailsQuery_givenNoDungeonRouteIds_excludesFilelessThumbnails(): void
    {
        // Arrange
        $filelessThumbnail   = $this->createDungeonRouteWithThumbnail(false);
        $fileBackedThumbnail = $this->createDungeonRouteWithThumbnail(true);

        try {
            // Act
            $result = $this->repository->fileBackedThumbnailsQuery()->pluck('id');

            // Assert - unscoped, so it must find the file-backed thumbnail regardless of route,
            // but never a fileless one.
            $this->assertTrue($result->contains($fileBackedThumbnail->id));
            $this->assertFalse($result->contains($filelessThumbnail->id));
        } finally {
            $filelessThumbnail->delete();
            File::whereKey($fileBackedThumbnail->file_id)->delete();
            $fileBackedThumbnail->delete();
            DungeonRoute::whereKey([$filelessThumbnail->dungeon_route_id, $fileBackedThumbnail->dungeon_route_id])->delete();
        }
    }
}
