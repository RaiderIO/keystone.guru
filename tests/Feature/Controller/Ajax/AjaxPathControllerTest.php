<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\Models\Path\PathChangedEvent;
use App\Models\DungeonRoute\DungeonRouteChange;
use App\Models\Floor\Floor;
use App\Models\Path;
use App\Models\Polyline;
use Exception;
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
    public function store_givenNewValidPath_broadcastsPayloadWithoutVerticesOrCoordinates(): void
    {
        // Arrange
        Event::fake([PathChangedEvent::class]);

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
        $response->assertCreated();

        // Assert - neither the raw vertices nor the computed model_data.coordinates are
        // broadcast: a path can have enough vertices to exceed Reverb's message size cap
        // (#3909). Collaborating clients fetch them via GET .../path/{path} instead. The rest of
        // the polyline (used for e.g. color) must still be present.
        Event::assertDispatched(PathChangedEvent::class, function (PathChangedEvent $event) use ($polyline) {
            $broadcastPayload = $event->broadcastWith();

            $this->assertArrayNotHasKey('vertices_json', $broadcastPayload['model']['polyline']);
            $this->assertEquals($polyline['color'], $broadcastPayload['model']['polyline']['color']);
            $this->assertArrayNotHasKey('model_data', $broadcastPayload);

            return true;
        });
    }

    #[Test]
    #[Group('Controller')]
    public function show_givenExistingPath_returnsCoordinatesData(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        $createResponse = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);
        $createResponse->assertCreated();
        $pathId = json_decode($createResponse->content(), true)['id'];

        // Act
        $response = $this->get(route('ajax.dungeonroute.path.show', [
            'dungeonRoute' => $this->dungeonRoute,
            'path'         => $pathId,
        ]));

        // Assert
        $response->assertOk();
        $responseArr = json_decode($response->content(), true);
        $this->assertArrayHasKey('coordinates', $responseArr['model_data']);
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

    /**
     * Guards #4264: delete() cascaded the path's awakened obelisk links and its polyline away
     * (Path::deleting) and only then wrote the change log row and touched the route, with no
     * transaction around any of it. A failure at the change log left the path and its children
     * permanently gone while the route's change log - the team's audit trail - had no record that
     * it ever happened.
     */
    #[Test]
    #[Group('Controller')]
    public function delete_givenTheChangeLogWriteFails_rollsBackThePathDelete(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $createResponse = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => PolylineFixtures::createPolyline($randomFloor),
        ]);
        $createResponse->assertCreated();

        $pathId          = json_decode($createResponse->content(), true)['id'];
        $polylinesBefore = Polyline::query()->count();

        // Fail the change log write, which is the write that follows the cascading delete
        DungeonRouteChange::creating(static function (): never {
            throw new Exception('Simulated failure writing the change log');
        });

        try {
            // Act
            $response = $this->delete(route('ajax.dungeonroute.path.delete', [
                'dungeonRoute' => $this->dungeonRoute,
                'path'         => $pathId,
            ]));

            // Assert - the client is told it failed, and the path is still there to delete again
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertEquals(1, $this->dungeonRoute->paths()->count());
            $this->assertEquals($polylinesBefore, Polyline::query()->count());
        } finally {
            // Remove only the listener registered above - DungeonRouteChange::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
        }
    }
}
