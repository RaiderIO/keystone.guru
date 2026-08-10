<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('EnemyForcesCheckpoint')]
final class AjaxEnemyForcesCheckpointControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function store_givenValidCheckpoint_createsIt(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesCheckpointId = null;

        try {
            // Act
            $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/enemyforcescheckpoint', $mappingVersion->id), [
                'id'                 => -1,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'name'               => 'Test corridor',
                'lat'                => -128.5,
                'lng'                => 192.5,
            ]);

            // Assert
            $response->assertSuccessful();

            $enemyForcesCheckpointId = $response->json('id');
            $this->assertNotNull($enemyForcesCheckpointId);

            $this->assertDatabaseHas('enemy_forces_checkpoints', [
                'id'                 => $enemyForcesCheckpointId,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'name'               => 'Test corridor',
            ]);
        } finally {
            if ($enemyForcesCheckpointId !== null) {
                EnemyForcesCheckpoint::where('id', $enemyForcesCheckpointId)->delete();
            }
        }
    }

    #[Test]
    public function store_givenNoName_stillCreatesIt(): void
    {
        // A checkpoint is placed on the map first and named afterwards in its popup, so the very first
        // save legitimately carries no name. Requiring one made placing a checkpoint fail with a 422.
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesCheckpointId = null;

        try {
            // Act
            $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/enemyforcescheckpoint', $mappingVersion->id), [
                'id'                 => -1,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'lat'                => -128.5,
                'lng'                => 192.5,
            ]);

            // Assert
            $response->assertSuccessful();

            $enemyForcesCheckpointId = $response->json('id');
            $this->assertNotNull($enemyForcesCheckpointId);
            $this->assertNull(EnemyForcesCheckpoint::findOrFail($enemyForcesCheckpointId)->name);
        } finally {
            if ($enemyForcesCheckpointId !== null) {
                EnemyForcesCheckpoint::where('id', $enemyForcesCheckpointId)->delete();
            }
        }
    }

    #[Test]
    public function store_givenMissingLatLng_failsValidation(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        // Assert the FormRequest itself rather than the rendered response: how this application turns a
        // ValidationException into a response on /ajax/ paths is a pre-existing, app-wide concern.
        $this->withoutExceptionHandling();
        $this->expectException(ValidationException::class);

        // Act
        $this->postJson(sprintf('/ajax/admin/mappingVersion/%d/enemyforcescheckpoint', $mappingVersion->id), [
            'id'                 => -1,
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
        ]);
    }

    #[Test]
    public function store_givenBroadcastFails_stillCreatesCheckpoint(): void
    {
        // Arrange - a broadcast failure (e.g. Reverb briefly unreachable) must not turn a
        // successfully saved model into an error response (#3941)
        $this->forceBroadcastFailure();

        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesCheckpointId = null;

        try {
            // Act
            $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/enemyforcescheckpoint', $mappingVersion->id), [
                'id'                 => -1,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'name'               => 'Test corridor',
                'lat'                => -128.5,
                'lng'                => 192.5,
            ]);

            // Assert
            $response->assertSuccessful();

            $enemyForcesCheckpointId = $response->json('id');
            $this->assertNotNull($enemyForcesCheckpointId);

            $this->assertDatabaseHas('enemy_forces_checkpoints', [
                'id'                 => $enemyForcesCheckpointId,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'name'               => 'Test corridor',
            ]);
        } finally {
            if ($enemyForcesCheckpointId !== null) {
                EnemyForcesCheckpoint::where('id', $enemyForcesCheckpointId)->delete();
            }
        }
    }

    #[Test]
    public function delete_givenCheckpointWithEnemies_deletesItAndReleasesItsEnemies(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesCheckpoint = EnemyForcesCheckpoint::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);

        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $mappingVersion->id)->firstOrFail();
        $enemy->update(['enemy_forces_checkpoint_id' => $enemyForcesCheckpoint->id]);

        try {
            // Act
            $response = $this->delete(sprintf(
                '/ajax/admin/mappingVersion/%d/enemyforcescheckpoint/%d',
                $mappingVersion->id,
                $enemyForcesCheckpoint->id,
            ));

            // Assert
            $response->assertSuccessful();

            $this->assertDatabaseMissing('enemy_forces_checkpoints', ['id' => $enemyForcesCheckpoint->id]);
            $this->assertNull(
                $enemy->fresh()->enemy_forces_checkpoint_id,
                'Deleting a checkpoint must release its member enemies - there are no FK constraints to do it.',
            );
        } finally {
            Enemy::where('enemy_forces_checkpoint_id', $enemyForcesCheckpoint->id)
                ->update(['enemy_forces_checkpoint_id' => null]);
            EnemyForcesCheckpoint::where('id', $enemyForcesCheckpoint->id)->delete();
        }
    }

    #[Test]
    public function delete_givenBroadcastFails_stillDeletesCheckpoint(): void
    {
        // Arrange - a broadcast failure must not turn an already-succeeded delete into an error
        // response describing a state that isn't true (#3941)
        $this->forceBroadcastFailure();

        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesCheckpoint = EnemyForcesCheckpoint::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);

        try {
            // Act
            $response = $this->delete(sprintf(
                '/ajax/admin/mappingVersion/%d/enemyforcescheckpoint/%d',
                $mappingVersion->id,
                $enemyForcesCheckpoint->id,
            ));

            // Assert
            $response->assertSuccessful();

            $this->assertDatabaseMissing('enemy_forces_checkpoints', ['id' => $enemyForcesCheckpoint->id]);
        } finally {
            EnemyForcesCheckpoint::where('id', $enemyForcesCheckpoint->id)->delete();
        }
    }

    #[Test]
    public function enemyStore_givenEnemyForcesCheckpointId_persistsMembership(): void
    {
        // A 200 from the enemy endpoint plus an updated pill is indistinguishable from the field being
        // silently dropped, so assert the column itself. This is the only thing that makes an enemy a
        // member of a checkpoint - there is no dedicated membership endpoint.
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesCheckpoint = EnemyForcesCheckpoint::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);

        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $mappingVersion->id)->firstOrFail();

        try {
            // Act
            // Send the enemy back exactly as it is, changing only its checkpoint - the same shape the map
            // editor PUTs.
            $payload                               = $enemy->getAttributes();
            $payload['enemy_forces_checkpoint_id'] = $enemyForcesCheckpoint->id;

            $response = $this->put(
                sprintf('/ajax/admin/mappingVersion/%d/enemy/%d', $mappingVersion->id, $enemy->id),
                $payload,
            );

            // Assert
            $response->assertSuccessful();
            $this->assertSame($enemyForcesCheckpoint->id, $enemy->fresh()->enemy_forces_checkpoint_id);
        } finally {
            Enemy::where('id', $enemy->id)->update(['enemy_forces_checkpoint_id' => null]);
            EnemyForcesCheckpoint::where('id', $enemyForcesCheckpoint->id)->delete();
        }
    }

    #[Test]
    public function store_givenNonAdminUser_isForbidden(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $this->be(User::factory()->create());

        // Act
        $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/enemyforcescheckpoint', $mappingVersion->id), [
            'id'                 => -1,
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);

        // Assert
        $this->assertContains($response->getStatusCode(), [401, 403]);
        $this->assertDatabaseMissing('enemy_forces_checkpoints', ['name' => 'Test corridor']);
    }

    /**
     * Swaps the default broadcast connection for one that always throws a BroadcastException,
     * simulating a Reverb outage without making a real network call.
     */
    private function forceBroadcastFailure(): void
    {
        Broadcast::extend('failing', static fn() => new class implements Broadcaster {
            public function auth($request)
            {
            }

            public function validAuthenticationResponse($request, $result)
            {
            }

            /**
             * @param array<int, mixed>    $channels
             * @param array<string, mixed> $payload
             */
            public function broadcast(array $channels, $event, array $payload = []): void
            {
                throw new BroadcastException('Simulated broadcast failure');
            }
        });

        config([
            'broadcasting.connections.failing' => ['driver' => 'failing'],
            'broadcasting.default'             => 'failing',
        ]);
    }

    private function getFloorWithEnemies(): Floor
    {
        /** @var Enemy $enemy */
        $enemy = Enemy::whereNotNull('floor_id')->firstOrFail();

        return Floor::findOrFail($enemy->floor_id);
    }

    private function getMappingVersionForFloor(Floor $floor): MappingVersion
    {
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $floor->dungeon->mappingVersions()->first();

        if ($mappingVersion === null) {
            $this->fail('Dungeon has no mapping versions.');
        }

        return $mappingVersion;
    }
}
