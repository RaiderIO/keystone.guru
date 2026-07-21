<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
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
    public function hasFreshThumbnailForVariant_givenThumbnailRenderedAfterLastContentChange_returnsTrue(): void
    {
        // Arrange
        $dungeon      = $this->getDungeonWithNonFacadeFloor();
        $floor        = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id]);

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
        $dungeon      = $this->getDungeonWithNonFacadeFloor();
        $floor        = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id]);

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
        $dungeon      = $this->getDungeonWithNonFacadeFloor();
        $floor        = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id]);

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
        $dungeon      = $this->getDungeonWithNonFacadeFloor();
        $floor        = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id]);

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
}
