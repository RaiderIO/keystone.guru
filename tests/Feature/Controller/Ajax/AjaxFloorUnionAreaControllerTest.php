<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingChangeLog;
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

    private const VERTICES_JSON = '[{"lat":-142.88,"lng":171.929},{"lat":-150,"lng":180.5},{"lat":-160.25,"lng":170}]';

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
    public function store_givenVerticesJson_storesItVerbatim(): void
    {
        // Arrange
        $floorUnionAreaId       = null;
        $lastMappingChangeLogId = (int)MappingChangeLog::query()->max('id');

        try {
            // Act
            $response = $this->post($this->createUrl(), $this->payload(self::VERTICES_JSON));

            // Assert
            $response->assertCreated();
            $floorUnionAreaId = $response->json('id');

            $this->assertSame(self::VERTICES_JSON, FloorUnionArea::query()->findOrFail($floorUnionAreaId)->vertices_json);
        } finally {
            $this->deleteFloorUnionArea($floorUnionAreaId);
            MappingChangeLog::query()->where('id', '>', $lastMappingChangeLogId)->delete();
        }
    }

    #[Test]
    public function store_givenFewerThanThreeVertices_returnsValidationError(): void
    {
        // Arrange
        $floorUnionAreaCount = FloorUnionArea::query()->count();

        // Act
        $response = $this->postJson($this->createUrl(), $this->payload('[{"lat":-142.88,"lng":171.929},{"lat":-150,"lng":180.5}]'));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['vertices_json']);
        $this->assertSame($floorUnionAreaCount, FloorUnionArea::query()->count());
    }

    #[Test]
    public function store_givenVerticesThatAreNotJson_returnsValidationError(): void
    {
        // Arrange
        $floorUnionAreaCount = FloorUnionArea::query()->count();

        // Act
        $response = $this->postJson($this->createUrl(), $this->payload('lat=-142.88&lng=171.929'));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['vertices_json']);
        $this->assertSame($floorUnionAreaCount, FloorUnionArea::query()->count());
    }

    private function createUrl(): string
    {
        return route('ajax.admin.floorunionarea.create', ['mappingVersion' => $this->mappingVersion]);
    }

    /** @return array<string, mixed> */
    private function payload(string $verticesJson): array
    {
        return [
            'id'                 => 0,
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floorUnion->floor_id,
            'floor_union_id'     => $this->floorUnion->id,
            'vertices_json'      => $verticesJson,
        ];
    }

    private function deleteFloorUnionArea(?int $floorUnionAreaId): void
    {
        if ($floorUnionAreaId !== null) {
            FloorUnionArea::query()->whereKey($floorUnionAreaId)->delete();
        }
    }
}
