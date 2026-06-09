<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Floor\Floor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Feature\Fixtures\PolylineFixtures;

#[Group('Controller')]
final class AjaxArrowControllerTest extends DungeonRouteTestBase
{
    #[Test]
    public function store_givenNewValidArrow_shouldReturnArrow(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->inRandomOrder()
            ->first();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [
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

    #[Test]
    public function store_givenNewEmptyArrow_shouldReturnFormValidationErrors(): void
    {
        // Arrange

        // Act
        $response = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [

        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['floor_id', 'polyline']);
    }

    #[Test]
    public function store_givenArrowWithValidButNotMatchingFloorId_shouldReturnError(): void
    {
        // Arrange
        $validIds  = $this->dungeonRoute->dungeon->floors->pluck('id');
        $allFloors = Floor::all()->keyBy('id');

        $randomInvalidId    = $allFloors->pluck('id')->diff($validIds)->random();
        $randomInvalidFloor = $allFloors->get($randomInvalidId);
        $polyline           = PolylineFixtures::createPolyline($randomInvalidFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomInvalidFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function store_givenArrowEmptyVertexCount_shouldReturnError(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor, collect());

        // Act
        $response = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['polyline.vertices_json']);
    }

    #[Test]
    public function delete_givenExistingArrow_shouldDeleteArrow(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->dungeonRoute;

            /** @var Floor $randomFloor */
            $randomFloor = $dungeonRoute->dungeon->floors()
                ->where('facade', false)
                ->inRandomOrder()
                ->first();

            $polyline = PolylineFixtures::createPolyline($randomFloor);

            $createResponse = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $dungeonRoute]), [
                'floor_id' => $randomFloor->id,
                'polyline' => $polyline,
            ]);

            $createResponse->assertCreated();
            $arrowId = json_decode($createResponse->content(), true)['id'];

            // Act
            $deleteResponse = $this->delete(route('ajax.dungeonroute.arrow.delete', [
                'dungeonRoute' => $dungeonRoute,
                'arrow'        => $arrowId,
            ]));

            // Assert
            $deleteResponse->assertNoContent();
            $this->assertEquals(0, $dungeonRoute->arrows()->count());
        } finally {
            // Route cleanup is handled by parent tearDown
        }
    }
}
