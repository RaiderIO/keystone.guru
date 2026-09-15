<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\CharacterClassSpecialization;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\PublishedState;
use App\Models\Team;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('DungeonRouteSaveService')]
final class DungeonRouteSaveServiceCloneTest extends DungeonRouteSaveServiceTestCase
{
    #[Test]
    public function cloneRoute_givenSourceRoute_createsRouteWithCloneOfAndNullTeamId(): void
    {
        // Arrange — cloneRoute uses Auth::id() for author_id which is NOT NULL in the DB
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create(['team_id' => null]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source);

            // Assert
            $this->assertTrue($clone->exists);
            $this->assertEquals($source->public_key, $clone->clone_of);
            $this->assertNull($clone->team_id);
            $this->assertNotEquals($source->public_key, $clone->public_key);
            $this->assertEquals($source->dungeon_id, $clone->dungeon_id);
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenUnpublishedTrue_setsUnpublishedPublishedState(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source, true);

            // Assert
            $this->assertEquals(
                PublishedState::ALL[PublishedState::UNPUBLISHED],
                $clone->published_state_id,
                'Cloning with unpublished=true must set published_state_id to UNPUBLISHED',
            );
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenUnpublishedFalse_copiesSourcePublishedState(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source, false);

            // Assert
            $this->assertEquals(
                PublishedState::ALL[PublishedState::WORLD],
                $clone->published_state_id,
                sprintf(
                    'Cloning with unpublished=false must copy published_state_id from source (%d)',
                    PublishedState::ALL[PublishedState::WORLD],
                ),
            );
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenTeamRouteWithTeamWideMapIcon_cloneGetsOnlyTheRoutesOwnIcons(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $team        = null;
        $source      = null;
        $clone       = null;
        $teamMapIcon = null;

        try {
            $dungeon = $this->getDungeonWithNonFacadeFloor();
            $floor   = $dungeon->floors()->where('facade', 0)->firstOrFail();

            $team = Team::create([
                'public_key'  => Team::generateRandomPublicKey(),
                'name'        => 'Clone route test team',
                'description' => 'Clone route test team',
            ]);

            $source = DungeonRoute::factory()->create([
                'dungeon_id' => $dungeon->id,
                'team_id'    => $team->id,
            ]);

            // An icon the route owns, and an icon that belongs to the team but not to this route
            $routeMapIcon = MapIcon::factory()->create([
                'dungeon_route_id'   => $source->id,
                'mapping_version_id' => null,
                'floor_id'           => $floor->id,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            ]);
            $teamMapIcon = MapIcon::factory()->create([
                'dungeon_route_id'   => null,
                'mapping_version_id' => null,
                'floor_id'           => $floor->id,
                'team_id'            => $team->id,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            ]);

            $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
            $thumbnailService->method('copyThumbnails')->willReturn(null);

            $service = $this->buildService(thumbnailService: $thumbnailService);

            // Act
            $clone = $service->cloneRoute($source);

            // Assert
            $cloneMapIcons = MapIcon::query()->where('dungeon_route_id', $clone->id)->get();
            $this->assertCount(1, $cloneMapIcons, 'The clone must only get the route\'s own map icon, not the team-wide one');
            $this->assertEquals($routeMapIcon->comment, $cloneMapIcons->first()->comment);
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            if ($source !== null) {
                $this->cleanupRoute($source);
            }
            $teamMapIcon?->delete();
            $team?->delete();
        }
    }

    #[Test]
    public function cloneRoute_givenSourceWithPlayerSpecializations_copiesSpecializationsToClone(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create(['team_id' => null]);
        $clone  = null;

        $specialization = CharacterClassSpecialization::query()->firstOrFail();
        DungeonRoutePlayerSpecialization::create([
            'dungeon_route_id'                  => $source->id,
            'character_class_specialization_id' => $specialization->id,
        ]);

        try {
            $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
            $thumbnailService->method('copyThumbnails')->willReturn(null);

            $service = $this->buildService(thumbnailService: $thumbnailService);

            // Act
            $clone = $service->cloneRoute($source);

            // Assert - re-query, cloneRelationsInto() mutates the source's loaded instances in place
            $cloneSpecializations = DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $clone->id)->get();
            $this->assertCount(1, $cloneSpecializations, 'The clone must keep the source\'s player specializations');
            $this->assertEquals($specialization->id, $cloneSpecializations->first()->character_class_specialization_id);
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }
}
