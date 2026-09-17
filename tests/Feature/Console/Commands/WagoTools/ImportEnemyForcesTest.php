<?php

namespace Tests\Feature\Console\Commands\WagoTools;

use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\WritesEnemyForcesDb2Tables;
use Tests\TestCases\PublicTestCase;

#[Group('EnemyForces')]
final class ImportEnemyForcesTest extends PublicTestCase
{
    use WritesEnemyForcesDb2Tables;

    private const string BUILD = '0.0.0.00004';

    #[\Override]
    protected function tearDown(): void
    {
        $this->removeDb2Tables();

        parent::tearDown();
    }

    #[Test]
    public function handle_givenAMovedTotalAndARetunedNpcWithoutWrite_reportsThemAndWritesNothing(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $retunedNpcId          = (int)array_key_first($ourEnemyForcesByNpcId);
        $db2EnemyForcesByNpcId = $ourEnemyForcesByNpcId;
        $db2EnemyForcesByNpcId[$retunedNpcId] *= 2;

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required + 31, $db2EnemyForcesByNpcId);

        // Act & Assert
        $this->artisan('wagotools:importenemyforces', $this->commandOptions())
            ->expectsOutputToContain(sprintf('Enemy forces required: %d -> %d', $mappingVersion->enemy_forces_required, $mappingVersion->enemy_forces_required + 31))
            ->expectsOutputToContain('Dry run - nothing was written')
            ->assertSuccessful();

        $this->assertSame($mappingVersion->enemy_forces_required, MappingVersion::findOrFail($mappingVersion->id)->enemy_forces_required);
        $this->assertSame($ourEnemyForcesByNpcId[$retunedNpcId], $this->getNpcEnemyForces($mappingVersion, $retunedNpcId));
    }

    #[Test]
    public function handle_givenAMovedTotalAndARetunedNpcWithWrite_writesThem(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $retunedNpcId          = (int)array_key_first($ourEnemyForcesByNpcId);
        $db2EnemyForcesByNpcId = $ourEnemyForcesByNpcId;
        $db2EnemyForcesByNpcId[$retunedNpcId] *= 2;

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required + 31, $db2EnemyForcesByNpcId);

        try {
            // Act & Assert
            $this->artisan('wagotools:importenemyforces', $this->commandOptions(['--write' => true]))
                ->expectsOutputToContain('Wrote 1 enemy forces rows and the required total')
                ->assertSuccessful();

            $this->assertSame($mappingVersion->enemy_forces_required + 31, MappingVersion::findOrFail($mappingVersion->id)->enemy_forces_required);
            $this->assertSame($ourEnemyForcesByNpcId[$retunedNpcId] * 2, $this->getNpcEnemyForces($mappingVersion, $retunedNpcId));
        } finally {
            MappingVersion::query()
                ->whereKey($mappingVersion->id)
                ->update([
                    'enemy_forces_required' => $mappingVersion->enemy_forces_required,
                    'updated_at'            => $mappingVersion->updated_at,
                ]);
            NpcEnemyForces::query()
                ->where('mapping_version_id', $mappingVersion->id)
                ->where('npc_id', $retunedNpcId)
                ->update(['enemy_forces' => $ourEnemyForcesByNpcId[$retunedNpcId]]);
        }
    }

    #[Test]
    public function handle_givenTheEnemyForcesWeAlreadyHave_reportsNothingToWrite(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required, $ourEnemyForcesByNpcId);

        // Act & Assert
        $this->artisan('wagotools:importenemyforces', $this->commandOptions(['--write' => true]))
            ->expectsOutputToContain('Nothing to write - the mapping version already matches this build.')
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenAnUnresolvableDungeon_failsAndWritesNothing(): void
    {
        // Arrange - a build we read wrong must not be able to write anything
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required + 31,
            $ourEnemyForcesByNpcId,
            dungeonEncounterMapId: $dungeon->map_id + 1,
        );

        // Act & Assert
        $this->artisan('wagotools:importenemyforces', $this->commandOptions(['--write' => true]))
            ->expectsOutputToContain('cannot be imported from product wow build')
            ->assertFailed();

        $this->assertSame($mappingVersion->enemy_forces_required, MappingVersion::findOrFail($mappingVersion->id)->enemy_forces_required);
    }

    #[Test]
    public function handle_givenNoDungeon_fails(): void
    {
        // Act & Assert
        $this->artisan('wagotools:importenemyforces', ['--product' => 'wow', '--build' => self::BUILD])
            ->expectsOutputToContain('Pass the dungeon to import with --dungeon')
            ->assertFailed();
    }

    #[Test]
    public function handle_givenNoProduct_fails(): void
    {
        // Act & Assert - the live client lags hotfixes, so reading it must be a choice
        $this->artisan('wagotools:importenemyforces', ['--dungeon' => self::DUNGEON_KEY, '--build' => self::BUILD])
            ->expectsOutputToContain('Pass the product to read with --product=wow or --product=wowt')
            ->assertFailed();
    }

    #[Test]
    public function handle_givenAnUnknownDungeon_fails(): void
    {
        // Act & Assert
        $this->artisan('wagotools:importenemyforces', ['--dungeon' => 'not-a-dungeon', '--product' => 'wow', '--build' => self::BUILD])
            ->expectsOutputToContain('Unknown dungeon not-a-dungeon')
            ->assertFailed();
    }

    protected function getDb2Build(): string
    {
        return self::BUILD;
    }

    /**
     * @param  array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function commandOptions(array $options = []): array
    {
        return ['--dungeon' => self::DUNGEON_KEY, '--product' => 'wow', '--build' => self::BUILD] + $options;
    }

    private function getNpcEnemyForces(MappingVersion $mappingVersion, int $npcId): ?int
    {
        $enemyForces = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('npc_id', $npcId)
            ->value('enemy_forces');

        return $enemyForces === null ? null : (int)$enemyForces;
    }
}
