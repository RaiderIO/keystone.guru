<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('DungeonFloorSwitchMarker')]
final class AjaxDungeonFloorSwitchMarkerControllerTest extends AjaxPublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function store_givenHiddenInFacade_createsTheMarker(): void
    {
        // Arrange
        [$mappingVersion, $floor] = $this->createMappingVersionAndFloor();

        try {
            // Act
            $response = $this->postJson($this->storeUrl($mappingVersion), $this->payload($mappingVersion, $floor, 1));

            // Assert
            $response->assertSuccessful();
            $this->assertTrue((bool)DungeonFloorSwitchMarker::query()->where('mapping_version_id', $mappingVersion->id)->firstOrFail()->hidden_in_facade);
        } finally {
            DungeonFloorSwitchMarker::query()->where('mapping_version_id', $mappingVersion->id)->delete();
        }
    }

    #[Test]
    public function store_givenAnEmptyHiddenInFacade_returnsValidationErrorAndCreatesNothing(): void
    {
        // Arrange
        [$mappingVersion, $floor] = $this->createMappingVersionAndFloor();

        try {
            // Act
            $response = $this->postJson($this->storeUrl($mappingVersion), $this->payload($mappingVersion, $floor, ''));

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['hidden_in_facade']);
            $this->assertSame(0, DungeonFloorSwitchMarker::query()->where('mapping_version_id', $mappingVersion->id)->count());
        } finally {
            DungeonFloorSwitchMarker::query()->where('mapping_version_id', $mappingVersion->id)->delete();
        }
    }

    /**
     * @return array{MappingVersion, Floor}
     */
    private function createMappingVersionAndFloor(): array
    {
        $dungeon = $this->createDungeon();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->firstOrFail();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();

        return [$mappingVersion, $floor];
    }

    private function storeUrl(MappingVersion $mappingVersion): string
    {
        return sprintf('/ajax/admin/mappingVersion/%d/dungeonfloorswitchmarker', $mappingVersion->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(MappingVersion $mappingVersion, Floor $floor, int|string $hiddenInFacade): array
    {
        return [
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'target_floor_id'    => $floor->id,
            'hidden_in_facade'   => $hiddenInFacade,
            'lat'                => -100,
            'lng'                => 100,
        ];
    }
}
