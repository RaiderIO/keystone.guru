<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('EnemyPatrol')]
final class AjaxEnemyPatrolControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    private MappingVersion $mappingVersion;

    private Floor $floor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The endpoints under test are admin-only
        $this->be(User::findOrFail(1));

        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, challengeMode: true, minEnemies: 1);

        $this->mappingVersion = $mappingVersion;

        /** @var Floor $floor */
        $floor       = $dungeon->floors()->where('facade', false)->firstOrFail();
        $this->floor = $floor;
    }

    #[Test]
    public function delete_givenAnExistingEnemyPatrol_deletesItAndItsPolyline(): void
    {
        // Arrange
        $enemyPatrol = $this->createEnemyPatrol();
        $polylineId  = $enemyPatrol->polyline_id;

        // Act
        $response = $this->delete($this->deleteUrl($enemyPatrol));

        // Assert
        $response->assertNoContent();
        $this->assertNull(EnemyPatrol::find($enemyPatrol->id));
        $this->assertNull(Polyline::find($polylineId));
    }

    /**
     * Guards #4264: delete() cascaded the patrol's polyline away (EnemyPatrol::deleting) and only
     * then wrote the mapping change log row, with no transaction around the pair. A failure at the
     * change log left the patrol and its polyline permanently gone with no record of the deletion
     * in the mapping change log, which is what the other environments replay the mapping from.
     */
    #[Test]
    public function delete_givenTheMappingChangeLogWriteFails_rollsBackTheEnemyPatrolDelete(): void
    {
        // Arrange
        $enemyPatrol = $this->createEnemyPatrol();
        $polylineId  = $enemyPatrol->polyline_id;

        // Fail the mapping change log write, which is the write that follows the cascading delete
        MappingChangeLog::creating(static function (): never {
            throw new Exception('Simulated failure writing the mapping change log');
        });

        try {
            // Act
            $response = $this->delete($this->deleteUrl($enemyPatrol));

            // Assert - the client is told it failed, and the patrol is still there to delete again
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertNotNull(EnemyPatrol::find($enemyPatrol->id));
            $this->assertNotNull(Polyline::find($polylineId));
        } finally {
            // Remove only the listener registered above - MappingChangeLog::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . MappingChangeLog::class);

            EnemyPatrol::query()->whereKey($enemyPatrol->id)->delete();
            Polyline::query()->whereKey($polylineId)->delete();
        }
    }

    private function createEnemyPatrol(): EnemyPatrol
    {
        $enemyPatrol = EnemyPatrol::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'polyline_id'        => -1,
            'teeming'            => null,
            'faction'            => 'any',
        ]);

        $polyline = Polyline::create([
            'model_id'       => $enemyPatrol->id,
            'model_class'    => EnemyPatrol::class,
            'color'          => '#f00000',
            'color_animated' => null,
            'weight'         => 2,
            'vertices_json'  => json_encode([['lat' => -100, 'lng' => 100], ['lat' => -120, 'lng' => 120]]),
        ]);

        $enemyPatrol->update(['polyline_id' => $polyline->id]);

        return $enemyPatrol;
    }

    private function deleteUrl(EnemyPatrol $enemyPatrol): string
    {
        return route('ajax.admin.enemypatrol.delete', [
            'mappingVersion' => $this->mappingVersion,
            'enemyPatrol'    => $enemyPatrol,
        ]);
    }
}
