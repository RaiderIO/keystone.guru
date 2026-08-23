<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Http\Controllers\Dungeon\MappingVersionController;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

/**
 * #4262: saveNew()/delete() cloned and deleted ten relation sets with no transaction, so a failure
 * partway through left a half-cloned or half-deleted mapping version that looked complete in the
 * admin UI. Both are now wrapped in DB::transaction() - these tests force a failure mid-clone/delete
 * (via a model event listener, not a DB constraint - this project uses none) and assert nothing that
 * was written before the failure survives.
 */
#[Group('MappingVersion')]
final class MappingVersionControllerAtomicityTest extends PublicTestCase
{
    #[Test]
    public function saveNew_givenAddMappingVersionActionAndCloneFailsMidway_rollsBackEntireMappingVersion(): void
    {
        // Arrange
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => $dungeon->getCurrentMappingVersion()?->enemies()->exists() ?? false);

        if ($dungeon === null) {
            $this->fail('No dungeon with enemies found for testing MappingVersion clone atomicity.');
        }

        $existingMappingVersion    = $dungeon->getCurrentMappingVersion();
        $mappingVersionCountBefore = $dungeon->mappingVersions()
            ->where('game_version_id', $existingMappingVersion->game_version_id)
            ->count();

        // Every Enemy clone in the new mapping version fails - some relation sets (e.g. dungeon floor
        // switch markers) are cloned before enemies in the boot() hook's merge order, so this proves a
        // PARTIAL clone (not just the very first write) gets rolled back too.
        Enemy::creating(static function (): bool {
            throw new RuntimeException('Simulated failure mid-clone');
        });

        $request = new Request([
            'game_version' => $existingMappingVersion->game_version_id,
            'action'       => 'Add mapping version',
        ]);
        $controller     = new MappingVersionController();
        $mappingService = $this->app->make(MappingServiceInterface::class);

        // Act
        try {
            $controller->saveNew($request, $dungeon, $mappingService);
            $this->fail('Expected the simulated mid-clone failure to propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated failure mid-clone', $exception->getMessage());
        }

        // Assert
        $mappingVersionCountAfter = $dungeon->mappingVersions()
            ->where('game_version_id', $existingMappingVersion->game_version_id)
            ->count();
        $this->assertSame(
            $mappingVersionCountBefore,
            $mappingVersionCountAfter,
            'A failure mid-clone must roll back the new mapping version row itself, not just leave it incomplete.',
        );
    }

    #[Test]
    public function delete_givenMapIconDeletionFailsMidway_rollsBackEntireDeletion(): void
    {
        // Arrange
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();

                return $mappingVersion !== null
                    && $mappingVersion->enemyPacks()->exists()
                    && $mappingVersion->mapIcons()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with enemy packs and map icons found for testing MappingVersion delete atomicity.');
        }

        $existingMappingVersion = $dungeon->getCurrentMappingVersion();

        $newMappingVersion = MappingVersion::create([
            'game_version_id'                 => $existingMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existingMappingVersion->version + 1000,
            'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        try {
            $enemyPackCountBefore = EnemyPack::where('mapping_version_id', $newMappingVersion->id)->count();
            $mapIconCountBefore   = MapIcon::where('mapping_version_id', $newMappingVersion->id)->count();
            $this->assertGreaterThan(0, $enemyPackCountBefore, 'Enemy packs should have been cloned into the new MappingVersion.');
            $this->assertGreaterThan(0, $mapIconCountBefore, 'Map icons should have been cloned into the new MappingVersion.');

            // Map icons are the one relation deleted one-by-one (not a mass delete) in the deleting()
            // hook, specifically so MapIcon::deleting fires - see the comment in MappingVersion::boot().
            // By this point enemyPacks()->delete() (a mass delete, earlier in the hook) has already run.
            MapIcon::deleting(static function (): bool {
                throw new RuntimeException('Simulated failure mid-delete');
            });

            $controller = new MappingVersionController();
            $request    = new Request();

            // Act
            try {
                $controller->delete($request, $dungeon, $newMappingVersion);
                $this->fail('Expected the simulated mid-delete failure to propagate.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Simulated failure mid-delete', $exception->getMessage());
            }

            // Assert
            $this->assertNotNull(
                MappingVersion::find($newMappingVersion->id),
                'A failure mid-delete must roll back the mapping version row itself, not leave it half-deleted.',
            );
            $this->assertSame(
                $enemyPackCountBefore,
                EnemyPack::where('mapping_version_id', $newMappingVersion->id)->count(),
                'A failure mid-delete must roll back relations already deleted by an earlier step (enemy packs).',
            );
            $this->assertSame(
                $mapIconCountBefore,
                MapIcon::where('mapping_version_id', $newMappingVersion->id)->count(),
                'A failure mid-delete must roll back the map icon whose deletion threw.',
            );
        } finally {
            // Guard: force-clean via the SAME deleting() cascade the production code uses, rather
            // than re-enumerating the ten relation sets here - that list would silently rot the
            // moment a relation is added to (or removed from) that cascade (Wotuu, PR #4268 review).
            // Just remove the listener that made MapIcon::deleting throw and let the real cascade run.
            Event::forget('eloquent.deleting: ' . MapIcon::class);
            $newMappingVersion->fresh()?->delete();
        }
    }
}
