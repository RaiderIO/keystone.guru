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
}
