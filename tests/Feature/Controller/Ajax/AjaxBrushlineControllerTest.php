<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\Models\Brushline\BrushlineChangedEvent;
use App\Models\Floor\Floor;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Feature\Fixtures\PolylineFixtures;

final class AjaxBrushlineControllerTest extends DungeonRouteTestBase
{
    #[Test]
    #[Group('Controller')]
    public function store_givenNewValidBrushline_shouldReturnBrushline(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->inRandomOrder()
            ->first();

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

    #[Test]
    #[Group('Controller')]
    public function store_givenNewValidBrushline_broadcastsPayloadWithoutRedundantVerticesJson(): void
    {
        // Arrange
        Event::fake([BrushlineChangedEvent::class]);

        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->inRandomOrder()
            ->first();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.brushline.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);
        $response->assertCreated();

        // Assert - model.polyline.vertices_json is dead weight: BrushlineChangedHandler always
        // overwrites it client-side with model_data.coordinates before use, so broadcasting both
        // just duplicated the vertex payload and could exceed Reverb's message size cap (#3909).
        // The rest of the polyline (used for e.g. color) must still be present.
        Event::assertDispatched(BrushlineChangedEvent::class, function (BrushlineChangedEvent $event) use ($polyline) {
            $broadcastPayload = $event->broadcastWith();

            $this->assertArrayNotHasKey('vertices_json', $broadcastPayload['model']['polyline']);
            $this->assertEquals($polyline['color'], $broadcastPayload['model']['polyline']['color']);
            $this->assertArrayHasKey('coordinates', $broadcastPayload['model_data']);

            return true;
        });
    }

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
