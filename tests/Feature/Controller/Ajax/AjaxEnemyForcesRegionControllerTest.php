<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Enemy;
use App\Models\EnemyForcesRegion;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('EnemyForcesRegion')]
final class AjaxEnemyForcesRegionControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function store_givenValidRegion_createsIt(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesRegionId = null;

        try {
            // Act
            $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/enemyforcesregion', $mappingVersion->id), [
                'id'                 => -1,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'name'               => 'Test corridor',
                'lat'                => -128.5,
                'lng'                => 192.5,
            ]);

            // Assert
            $response->assertSuccessful();

            $enemyForcesRegionId = $response->json('id');
            $this->assertNotNull($enemyForcesRegionId);

            $this->assertDatabaseHas('enemy_forces_regions', [
                'id'                 => $enemyForcesRegionId,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'name'               => 'Test corridor',
            ]);
        } finally {
            if ($enemyForcesRegionId !== null) {
                EnemyForcesRegion::where('id', $enemyForcesRegionId)->delete();
            }
        }
    }

    #[Test]
    public function store_givenMissingName_failsValidation(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        // Assert the FormRequest itself rather than the rendered response: how this application turns a
        // ValidationException into a response on /ajax/ paths is a pre-existing, app-wide concern.
        $this->withoutExceptionHandling();
        $this->expectException(ValidationException::class);

        // Act
        $this->postJson(sprintf('/ajax/admin/mappingVersion/%d/enemyforcesregion', $mappingVersion->id), [
            'id'                 => -1,
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);
    }

    #[Test]
    public function delete_givenRegionWithEnemies_deletesItAndReleasesItsEnemies(): void
    {
        // Arrange
        $floor          = $this->getFloorWithEnemies();
        $mappingVersion = $this->getMappingVersionForFloor($floor);

        $enemyForcesRegion = EnemyForcesRegion::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);

        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $mappingVersion->id)->firstOrFail();
        $enemy->update(['enemy_forces_region_id' => $enemyForcesRegion->id]);

        try {
            // Act
            $response = $this->delete(sprintf(
                '/ajax/admin/mappingVersion/%d/enemyforcesregion/%d',
                $mappingVersion->id,
                $enemyForcesRegion->id,
            ));

            // Assert
            $response->assertSuccessful();

            $this->assertDatabaseMissing('enemy_forces_regions', ['id' => $enemyForcesRegion->id]);
            $this->assertNull(
                $enemy->fresh()->enemy_forces_region_id,
                'Deleting a region must release its member enemies - there are no FK constraints to do it.',
            );
        } finally {
            Enemy::where('enemy_forces_region_id', $enemyForcesRegion->id)
                ->update(['enemy_forces_region_id' => null]);
            EnemyForcesRegion::where('id', $enemyForcesRegion->id)->delete();
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
        $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/enemyforcesregion', $mappingVersion->id), [
            'id'                 => -1,
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'name'               => 'Test corridor',
            'lat'                => -128.5,
            'lng'                => 192.5,
        ]);

        // Assert
        $this->assertContains($response->getStatusCode(), [401, 403]);
        $this->assertDatabaseMissing('enemy_forces_regions', ['name' => 'Test corridor']);
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
