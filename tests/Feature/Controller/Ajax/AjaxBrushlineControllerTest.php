<?php

namespace Tests\Feature\Controller\Ajax;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Floor\Floor;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Feature\Fixtures\PolylineFixtures;

class AjaxBrushlineControllerTest extends DungeonRouteTestBase
{
    /**
     *
     * @return void
     */
    #[Test]
    #[Group('Controller')]
    public function store_givenNewValidBrushline_shouldReturnBrushline(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.brushline.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->assertEquals($randomFloor->id, $responseArr['floor_id']);
        $this->assertEquals($polyline['color'], $responseArr['polyline']['color']);
        $this->assertEquals($polyline['color_animated'], $responseArr['polyline']['color_animated']);
        $this->assertEquals($polyline['weight'], $responseArr['polyline']['weight']);
        $this->assertEquals($polyline['vertices_json'], $responseArr['polyline']['vertices_json']);
    }

    /**
     *
     * @return void
     */
    #[Test]
    #[Group('Controller')]
    public function store_givenNewEmptyBrushline_shouldReturnFormValidationErrors(): void
    {
        // Arrange

        // Act
        $response = $this->post(route('ajax.dungeonroute.brushline.create', ['dungeonRoute' => $this->dungeonRoute]), [

        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['floor_id', 'polyline']);
    }


    /**
     *
     * @return void
     */
    #[Test]
    #[Group('Controller')]
    public function store_givenBrushlineWithValidButNotMatchingFloorId_shouldReturnError(): void
    {
        // Arrange
        $validIds  = $this->dungeonRoute->dungeon->floors->pluck('id');
        $allFloors = Floor::all()->keyBy('id');

        $randomInvalidId    = $allFloors->pluck('id')->diff($validIds)->random();
        $randomInvalidFloor = $allFloors->get($randomInvalidId);
        $polyline           = PolylineFixtures::createPolyline($randomInvalidFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.brushline.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomInvalidFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     *
     * @return void
     */
    #[Test]
    #[Group('Controller')]
    public function store_givenBrushlineEmptyVertexCount_shouldReturnError(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor, collect());

        // Act
        $response = $this->post(route('ajax.dungeonroute.brushline.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['polyline.vertices_json']);
    }
}
