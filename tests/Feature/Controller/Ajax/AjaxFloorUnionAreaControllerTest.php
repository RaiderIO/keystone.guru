<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('Facade')]
final class AjaxFloorUnionAreaControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    private MappingVersion $mappingVersion;

    private FloorUnion $floorUnion;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        [, $mappingVersion] = $this->findDungeon(facadeEnabled: true);

        $this->mappingVersion = $mappingVersion;
        $this->floorUnion     = $mappingVersion->floorUnions()->firstOrFail();
    }

    #[Test]
    public function store_givenFormEncodedVertices_storesNumericCoordinates(): void
    {
        // Arrange
        $floorUnionAreaId = null;

        try {
            // Act
            $response = $this->post($this->createUrl(), $this->payload([
                ['lat' => '-142.88', 'lng' => '171.929'],
                ['lat' => '-150', 'lng' => '180.5'],
                ['lat' => '-160.25', 'lng' => '170'],
            ]));

            // Assert
            $response->assertCreated();
            $floorUnionAreaId = $response->json('id');

            $storedVerticesJson = FloorUnionArea::query()->findOrFail($floorUnionAreaId)->vertices_json;
            $this->assertSame(
                '[{"lat":-142.88,"lng":171.929},{"lat":-150,"lng":180.5},{"lat":-160.25,"lng":170}]',
                $storedVerticesJson,
            );
        } finally {
            $this->deleteFloorUnionArea($floorUnionAreaId);
        }
    }

    #[Test]
    public function store_givenNonNumericVertex_returnsValidationError(): void
    {
        // Arrange
        $floorUnionAreaCount = FloorUnionArea::query()->count();

        // Act
        $response = $this->postJson($this->createUrl(), $this->payload([
            ['lat' => '-142.88', 'lng' => '171.929'],
            ['lat' => 'north', 'lng' => '180.5'],
            ['lat' => '-160.25', 'lng' => '170'],
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['vertices.1.lat']);
        $this->assertSame($floorUnionAreaCount, FloorUnionArea::query()->count());
    }

    private function createUrl(): string
    {
        return route('ajax.admin.floorunionarea.create', ['mappingVersion' => $this->mappingVersion]);
    }

    /**
     * @param array<int, array{lat: string, lng: string}> $vertices
     *
     * @return array<string, mixed>
     */
    private function payload(array $vertices): array
    {
        return [
            'id'                 => 0,
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floorUnion->floor_id,
            'floor_union_id'     => $this->floorUnion->id,
            'vertices'           => $vertices,
        ];
    }

    private function deleteFloorUnionArea(?int $floorUnionAreaId): void
    {
        if ($floorUnionAreaId !== null) {
            FloorUnionArea::query()->whereKey($floorUnionAreaId)->delete();
        }
    }
}
