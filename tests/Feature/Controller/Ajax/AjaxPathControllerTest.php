<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\Models\Path\PathChangedEvent;
use App\Models\Floor\Floor;
use App\Models\Path;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Feature\Fixtures\PolylineFixtures;

final class AjaxPathControllerTest extends DungeonRouteTestBase
{
    #[Test]
    #[Group('Controller')]
    public function store_givenNewValidPath_shouldReturnPath(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [
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
    public function store_givenNewValidPath_broadcastsPayloadWithoutRedundantVerticesJson(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        $response = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);
        $response->assertCreated();

        /** @var Path $path */
        $path = Path::findOrFail(json_decode($response->content(), true)['id']);

        $event = new PathChangedEvent(
            app(CoordinatesServiceInterface::class),
            $this->dungeonRoute,
            User::findOrFail(1),
            $path,
        );

        // Act
        $broadcastPayload = $event->broadcastWith();

        // Assert - model.polyline.vertices_json is dead weight: PathChangedHandler always
        // overwrites it client-side with model_data.coordinates before use, so broadcasting both
        // just tripled the vertex payload and could exceed Reverb's message size cap on large
        // paths (#3909). The rest of the polyline (used for e.g. color) must still be present.
        $this->assertArrayNotHasKey('vertices_json', $broadcastPayload['model']['polyline']);
        $this->assertEquals($polyline['color'], $broadcastPayload['model']['polyline']['color']);
        $this->assertArrayHasKey('coordinates', $broadcastPayload['model_data']);
    }

    #[Test]
    #[Group('Controller')]
    public function store_givenNewEmptyPath_shouldReturnFormValidationErrors(): void
    {
        // Arrange

        // Act
        $response = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [

        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['floor_id', 'polyline']);
    }

    #[Test]
    #[Group('Controller')]
    public function store_givenPathWithValidButNotMatchingFloorId_shouldReturnError(): void
    {
        // Arrange
        $validIds  = $this->dungeonRoute->dungeon->floors->pluck('id');
        $allFloors = Floor::all()->keyBy('id');

        $randomInvalidId    = $allFloors->pluck('id')->diff($validIds)->random();
        $randomInvalidFloor = $allFloors->get($randomInvalidId);
        $polyline           = PolylineFixtures::createPolyline($randomInvalidFloor);

        // Act
        $response = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomInvalidFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    #[Group('Controller')]
    public function store_givenPathEmptyVertexCount_shouldReturnError(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor, collect());

        // Act
        $response = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['polyline.vertices_json']);
    }

    #[Test]
    #[Group('Controller')]
    public function store_givenUpdateThatFailsToSave_shouldReturnNotFoundAndRollBackTransaction(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $path = Path::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => $randomFloor->id,
            'polyline_id'      => -1,
        ]);

        // Desync the stored floor_id from what the request below will submit, so that
        // Path::update() actually has a dirty attribute and fires its 'updating' event -
        // Eloquent skips the event entirely (and always returns true) when nothing changed
        Path::query()->where('id', $path->id)->update(['floor_id' => $randomFloor->id + 1]);

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        // Force Path::update() to fail, exercising the same 'unable to save' branch a real DB
        // failure would take, without mocking the global \Exception the controller now catches
        Path::updating(fn() => false);

        try {
            // Act
            $response = $this->put(route('ajax.dungeonroute.path.update', [
                'dungeonRoute' => $this->dungeonRoute,
                'path'         => $path,
            ]), [
                'floor_id' => $randomFloor->id,
                'polyline' => $polyline,
            ]);

            // Assert
            $response->assertStatus(StatusCode::NOT_FOUND);
            // The polyline creation happens after the failed update, inside the same transaction -
            // it must not have been persisted
            $this->assertDatabaseMissing((new Polyline())->getTable(), [
                'model_id'    => $path->id,
                'model_class' => Path::class,
            ]);
        } finally {
            // Remove only the listener registered above - Path::flushEventListeners() would also
            // wipe Path::boot()'s cascade-delete listener for the rest of the PHPUnit process
            Event::forget('eloquent.updating: ' . Path::class);
            $path->delete();
        }
    }
}
