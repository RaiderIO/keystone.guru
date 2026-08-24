<?php

namespace Tests\Feature\App\Service\Floor;

use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\Floor\FloorResolutionServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Service')]
#[Group('Floor')]
final class FloorResolutionServiceTest extends PublicTestCase
{
    use ProvidesDungeon;

    private ?string $originalAdminMapFacadeStyle = null;

    private User $admin;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->admin                       = User::findOrFail(1);
        $this->originalAdminMapFacadeStyle = $this->admin->map_facade_style;
        $this->admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);
        $this->be($this->admin);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            User::findOrFail(1)->update(['map_facade_style' => $this->originalAdminMapFacadeStyle]);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function resolveDefaultFloor_givenNonFacadeDungeon_returnsFloorFlaggedDefault(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, requireDefaultFloor: true);
        /** @var Floor $expected */
        $expected = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $floor = app(FloorResolutionServiceInterface::class)->resolveDefaultFloor($dungeon, $mappingVersion);

        // Assert
        $this->assertSame($expected->id, $floor->id);
    }

    #[Test]
    public function resolveDefaultFloor_givenFacadeDungeon_returnsFacadeFloor(): void
    {
        // Arrange
        $this->admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, requireDefaultFloor: true);
        /** @var Floor $expected */
        $expected = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $floor = app(FloorResolutionServiceInterface::class)->resolveDefaultFloor($dungeon, $mappingVersion);

        // Assert
        $this->assertSame($expected->id, $floor->id);
        $this->assertSame(1, $floor->facade);
    }

    #[Test]
    public function resolveRequestedFloor_givenExistingFloorIndex_returnsThatFloorAsCanonical(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->firstOrFail();

        // Act
        $resolved = app(FloorResolutionServiceInterface::class)->resolveRequestedFloor($dungeon, $mappingVersion, (string)$floor->index);

        // Assert
        $this->assertSame($floor->id, $resolved->floor->id);
        $this->assertTrue($resolved->isCanonical);
        $this->assertTrue($resolved->floorWasFound);
    }

    #[Test]
    public function resolveRequestedFloor_givenNonExistentFloorIndex_fallsBackToDefaultFloorAsNonCanonical(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, requireDefaultFloor: true);
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $resolved = app(FloorResolutionServiceInterface::class)->resolveRequestedFloor($dungeon, $mappingVersion, '999999');

        // Assert - Floor::indexOrFacade()'s query itself falls back to the default floor via
        // `orWhere('default', 1)`, so this still counts as "found" (floorWasFound) even though it
        // isn't the floor that was actually requested (isCanonical)
        $this->assertSame($defaultFloor->id, $resolved->floor->id);
        $this->assertFalse($resolved->isCanonical);
        $this->assertTrue($resolved->floorWasFound);
    }

    #[Test]
    public function resolveRequestedFloor_givenNonNumericFloorIndex_behavesAsFloorIndexOne(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, requireDefaultFloor: true);
        $expectedService            = app(FloorResolutionServiceInterface::class);
        $expected                   = $expectedService->resolveRequestedFloor($dungeon, $mappingVersion, '1');

        // Act
        $resolved = $expectedService->resolveRequestedFloor($dungeon, $mappingVersion, 'not-a-number');

        // Assert
        $this->assertSame($expected->floor->id, $resolved->floor->id);
        $this->assertSame($expected->isCanonical, $resolved->isCanonical);
    }

    #[Test]
    public function resolveRequestedFloor_givenFacadeStyleAndFacadeEnabledDungeon_returnsFacadeFloor(): void
    {
        // Arrange
        $this->admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, requireDefaultFloor: true);

        // Act
        $resolved = app(FloorResolutionServiceInterface::class)->resolveRequestedFloor($dungeon, $mappingVersion, '1');

        // Assert
        $this->assertSame(1, $resolved->floor->facade);
    }
}
