<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\Models\Arrow\ArrowChangedEvent;
use App\Models\DungeonRoute\DungeonRouteChange;
use App\Models\Floor\Floor;
use App\Models\Polyline;
use Exception;
use Illuminate\Support\Facades\Event;
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
    public function store_givenNewValidArrow_broadcastsPayloadWithoutVerticesOrCoordinates(): void
    {
        // Arrange
        Event::fake([ArrowChangedEvent::class]);

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
        $response->assertCreated();

        // Assert - neither the raw vertices nor the computed model_data.coordinates are
        // broadcast: an arrow can have enough vertices to exceed Reverb's message size cap
        // (#3909). Collaborating clients fetch them via GET .../arrow/{arrow} instead. The rest
        // of the polyline (used for e.g. color) must still be present.
        Event::assertDispatched(ArrowChangedEvent::class, function (ArrowChangedEvent $event) use ($polyline) {
            $broadcastPayload = $event->broadcastWith();

            $this->assertArrayNotHasKey('vertices_json', $broadcastPayload['model']['polyline']);
            $this->assertEquals($polyline['color'], $broadcastPayload['model']['polyline']['color']);
            $this->assertArrayNotHasKey('model_data', $broadcastPayload);

            return true;
        });
    }

    #[Test]
    public function show_givenExistingArrow_returnsCoordinatesData(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->inRandomOrder()
            ->first();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        $createResponse = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => $polyline,
        ]);
        $createResponse->assertCreated();
        $arrowId = json_decode($createResponse->content(), true)['id'];

        // Act
        $response = $this->get(route('ajax.dungeonroute.arrow.show', [
            'dungeonRoute' => $this->dungeonRoute,
            'arrow'        => $arrowId,
        ]));

        // Assert
        $response->assertOk();
        $responseArr = json_decode($response->content(), true);
        $this->assertArrayHasKey('coordinates', $responseArr['model_data']);
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

    /**
     * Guards #4259: the catch used to sit *inside* the DB::transaction() closure, so the closure
     * returned normally and Laravel committed. A failure between the two writes that
     * SavesPolylines::savePolylineToModel() performs - Polyline::updateOrCreate() and then
     * $ownerModel->update(['polyline_id' => ...]) - therefore left a committed arrow row still
     * carrying the polyline_id = -1 sentinel plus an orphan polyline, while responding 404. The
     * client treats that as a failure and retries, so one drawn line became two rows.
     */
    #[Test]
    #[Group('Controller')]
    public function store_givenThePolylineWriteFails_rollsBackTheWholeArrow(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $polyline = PolylineFixtures::createPolyline($randomFloor);

        $polylinesBefore = Polyline::query()->count();

        // Fail the polyline write specifically, which is what a contended `polylines` table does in
        // production (#4239). By that point the arrow row has already been inserted inside the
        // same transaction, which is exactly the state that used to get committed.
        Polyline::creating(static function (): never {
            throw new Exception('Simulated failure writing the polyline');
        });

        try {
            // Act
            $response = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [
                'floor_id' => $randomFloor->id,
                'polyline' => $polyline,
            ]);

            // Assert - the client is told it failed, and nothing was left behind for it to trip over
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertEquals(0, $this->dungeonRoute->arrows()->count());
            $this->assertEquals($polylinesBefore, Polyline::query()->count());
        } finally {
            // Remove only the listener registered above - Polyline::flushEventListeners() would also
            // wipe Polyline::boot()'s own listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . Polyline::class);
        }
    }

    /**
     * Guards #4264: delete() cascaded the arrow's polyline away (Arrow::deleting) and only then
     * wrote the change log row and touched the route, with no transaction around any of it. A
     * failure at the change log left the arrow and its polyline permanently gone while the route's
     * change log - the team's audit trail - had no record that it ever happened.
     */
    #[Test]
    #[Group('Controller')]
    public function delete_givenTheChangeLogWriteFails_rollsBackTheArrowDelete(): void
    {
        // Arrange
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        $createResponse = $this->post(route('ajax.dungeonroute.arrow.create', ['dungeonRoute' => $this->dungeonRoute]), [
            'floor_id' => $randomFloor->id,
            'polyline' => PolylineFixtures::createPolyline($randomFloor),
        ]);
        $createResponse->assertCreated();

        $arrowId         = json_decode($createResponse->content(), true)['id'];
        $polylinesBefore = Polyline::query()->count();

        // Fail the change log write, which is the write that follows the cascading delete
        DungeonRouteChange::creating(static function (): never {
            throw new Exception('Simulated failure writing the change log');
        });

        try {
            // Act
            $response = $this->delete(route('ajax.dungeonroute.arrow.delete', [
                'dungeonRoute' => $this->dungeonRoute,
                'arrow'        => $arrowId,
            ]));

            // Assert - the client is told it failed, and the arrow is still there to delete again
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertEquals(1, $this->dungeonRoute->arrows()->count());
            $this->assertEquals($polylinesBefore, Polyline::query()->count());
        } finally {
            // Remove only the listener registered above - DungeonRouteChange::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
        }
    }
}
